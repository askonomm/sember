use std::{fs, collections::HashMap};
use rust_embed::RustEmbed;
use serde::{Deserialize, Serialize};
use siena::siena::{Siena, Store, Record, RecordSortOrder};
use tera::{Context, Tera};

#[derive(Debug, Clone, Default, Serialize, Deserialize)]
struct SiteConfig {
    accent: Option<String>,
    show_cv: Option<bool>,
    show_journal: Option<bool>,
}

#[derive(Debug, Serialize, Deserialize)]
struct AboutConfig {
    name: String,
    email: Option<String>,
    photo: Option<String>,
    location: Option<String>,
    description: Option<String>,
    description_long: Option<String>,
    skills: Option<Vec<String>>,
}

#[derive(Debug, Serialize, Deserialize)]
struct LinkConfig {
    label: String,
    url: String,
}

#[derive(Debug, Serialize, Deserialize)]
struct JobConfig {
    company: String,
    url: Option<String>,
    position: String,
    location: String,
    description: String,
    from: String,
    to: String,
}

#[derive(Debug, Serialize, Deserialize)]
struct EducationConfig {
    name: String,
    url: Option<String>,
    degree: String,
    field_of_study: String,
    from: String,
    to: String,
}

#[derive(Debug, Serialize, Deserialize)]
struct Config {
    site: Option<SiteConfig>,
    about: AboutConfig,
    links: Option<Vec<LinkConfig>>,
    jobs: Option<Vec<JobConfig>>,
    education: Option<Vec<EducationConfig>>,
    journal_posts: Option<Vec<Record>>,
}

#[derive(Debug, Serialize, Deserialize)]
#[serde(untagged)]
enum TemplateData {
    Str(String),
    Bool(bool),
}

#[derive(RustEmbed)]
#[folder = "views/"]
struct View;

#[derive(RustEmbed)]
#[folder = "assets/"]
struct Asset;

fn s(str: &str) -> String {
    return str.to_string();
}

fn compose_page_data(name: &str, config: &Config) -> HashMap<String, TemplateData> {
    let mut page_data = HashMap::new();

    // set name
    page_data.insert(s("name"), TemplateData::Str(name.to_string()));

    // set page title
    let title = match name {
        "index" => config.about.name.clone(),
        "cv" => format!("Résumé — {}", config.about.name.clone()),
        "journal" => format!("Journal — {}", config.about.name.clone()),
        _ => String::new(),
    };

    page_data.insert(s("title"), TemplateData::Str(title));

    // set accent
    let accent = config.site.clone().unwrap_or_default().accent.unwrap_or("#000".to_string());
    page_data.insert(s("accent"), TemplateData::Str(accent));

    // show cv?
    let show_cv = config.site.clone().unwrap_or_default().show_cv.unwrap_or(false);
    page_data.insert(s("show_cv"), TemplateData::Bool(show_cv));

    // show journal?
    let show_journal = config.site.clone().unwrap_or_default().show_journal.unwrap_or(false);
    page_data.insert(s("show_journal"), TemplateData::Bool(show_journal));

    return page_data;
}

fn compile_template_partials(name: &str, config: &Config) -> HashMap<String, String> {
    let partials_names = Vec::from(["head", "foot", "nav"]);
    let mut partials: HashMap<String, String> = HashMap::new();

    for partial in partials_names {
        let template_contents = View::get(&format!("partials/{}.twig", partial)).unwrap();
        let template_str = std::str::from_utf8(template_contents.data.as_ref()).unwrap();
        
        // create context
        let mut context = Context::new();
        context.insert("config", config);
        context.insert("page", &compose_page_data(name, &config));

        // create template
        let template = Tera::one_off(template_str, &context, false);
        
        if template.is_ok() {
            partials.insert(s(partial), template.unwrap());
        } else {
            println!("{:?}", template.err().unwrap());
            partials.insert(s(partial), format!("Failed to render partial: {}", partial));
        }
    }

    return partials;
}

fn compile_template(name: &str, config: &Config) -> String {
    let template_contents = View::get(&format!("{}.twig", name)).unwrap();
    let template_str = std::str::from_utf8(template_contents.data.as_ref()).unwrap();
    
    // create context
    let mut context = Context::new();
    context.insert("config", &config);
    context.insert("page", &compose_page_data(name, &config));
    context.insert("partials", &compile_template_partials(name, &config));
    
    // create template
    let template = Tera::one_off(template_str, &context, false);

    return template.unwrap();
}

fn no_config() -> bool {
    if fs::read("./sember.toml").is_ok() {
        return false;
    }

    return true;
}

fn read_journal_posts() -> Vec<Record> {
    let store = Siena::default().set_store(Store::Local {
        directory: "./".to_string()
    });

    return store.collection("journal")
        .when_is("status", "published")
        .sort("date", RecordSortOrder::Desc)
        .get_all();
}

/// Reads the `sember.toml` file into a `Config` struct.
fn read_config() -> Config {
    let config_str = fs::read_to_string("./sember.toml").unwrap_or(String::default());
    let mut config: Config = toml::from_str(&config_str).unwrap();
    
    config.journal_posts = Some(read_journal_posts());

    return config;
}

fn delete_public_dir() {
    fs::remove_dir_all("./public").unwrap_or(());
}

fn build_index(config: &Config) {
    let html = compile_template("index", config);

    fs::create_dir_all("./public").unwrap();
    fs::write("./public/index.html", html).unwrap();
}

fn build_cv(config: &Config) {
    let html = compile_template("cv", config);

    fs::create_dir_all("./public/cv").unwrap();
    fs::write("./public/cv/index.html", html).unwrap();
}

fn build_journal(config: &Config) {
    let html = compile_template("journal", config);

    fs::create_dir_all("./public/journal").unwrap();
    fs::write("./public/journal/index.html", html).unwrap();
}

fn copy_assets(config: &Config) {
    // css, fonts, etc
    fs::create_dir_all("./public/css").unwrap();
    fs::create_dir_all("./public/fonts").unwrap();

    for asset in Asset::iter() {   
        // naive solution for checking if we're dealing with a directory,
        // because I assume only files contain punctuation marks.  
        if !asset.as_ref().contains(".") {
            continue;
        }

        let asset_contents = Asset::get(asset.as_ref()).unwrap();

        if asset.ends_with(".woff") || asset.ends_with(".woff2") {
            let asset_data = asset_contents.data.as_ref();
            fs::write(&format!("./public/{}", asset), asset_data).unwrap();
        } else {
            let asset_str = std::str::from_utf8(asset_contents.data.as_ref()).unwrap();
            fs::write(&format!("./public/{}", asset), asset_str).unwrap();
        }
    }

    // if the user has a photo, and the file exists, copy that as well
    let photo = config.about.photo.clone();

    if photo.is_some() && fs::read(photo.as_ref().unwrap()).is_ok() {
        let from = photo.as_ref().unwrap();
        let to = format!("./public/{}", photo.as_ref().unwrap());

        fs::copy(from, to).unwrap();
    }
}

fn main() {
    if no_config() {
        panic!("No sember.toml file found. Cannot proceed without it.");
    }

    let config = read_config();

    // delete public dir
    delete_public_dir();

    // build index
    build_index(&config);

    // build cv
    build_cv(&config);

    // build blog
    build_journal(&config);

    // copy assets
    copy_assets(&config);
}
