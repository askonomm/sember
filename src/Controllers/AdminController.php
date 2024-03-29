<?php

namespace Asko\Sember\Controllers;

use Asko\Sember\Database;
use Asko\Sember\Helpers\BlockHelper;
use Asko\Sember\Models\Meta;
use Asko\Sember\Models\Post;
use Asko\Sember\Models\User;
use Asko\Sember\Request;
use Asko\Sember\Response;

readonly class AdminController
{
    public function __construct(private Database $db)
    {
    }

    /**
     * Redirect to the posts page if authenticated. Otherwise, redirect
     * to the sign-in page.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function index(Request $request, Response $response): Response
    {
        if (!$request->session()->has("auth_token")) {
            return $response->redirect("/admin/signin");
        }

        return $response->redirect("/admin/posts");
    }

    /**
     * Signs the user out.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function signOut(Request $request, Response $response): Response
    {
        $request->cookie()->remove("auth_token");

        return $response->redirect("/admin/signin");
    }

    /**
     * List all posts.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function posts(Request $request, Response $response): Response
    {
        $posts = $this->db
            ->find(
                model: Post::class,
                query: "order by created_at desc"
            )
            ->map(function (Post $post) {
                if ($post->get("status") === "published" && $post->get("published_at") <= time()) {
                    $post->set("status", "published");
                } elseif ($post->get("status") === "published" && $post->get("published_at") > time()) {
                    $post->set("status", "scheduled");
                } else {
                    $post->set("status", "draft");
                }

                return $post;
            })
            ->toArray();

        $site_name = $this->db->findOne(Meta::class, "where meta_name = ?", [
            "site_name",
        ]);

        return $response->view("admin/posts", [
            "posts" => $posts,
            "site_name" => $site_name?->get("meta_value") ?? "",
            "url" => $request->protocol() . "://" . $request->hostname(),
            "url_without_protocol" => $request->hostname(),
        ]);
    }

    /**
     * Create a new post.
     *
     * @param Response $response
     * @return Response
     */
    public function createPost(Response $response): Response
    {
        $user = User::current();

        if (!$user) {
            return $response->redirect("/admin/signin");
        }

        if (
            $id = $this->db->create(
                new Post([
                    "title" => "",
                    "slug" => "",
                    "content" => json_encode([BlockHelper::new("markdown")]),
                    "status" => "draft",
                    "user_id" => $user->get("id"),
                    "created_at" => time(),
                    "updated_at" => time(),
                ])
            )
        ) {
            return $response->redirect("/admin/posts/edit/{$id}");
        }

        return $response->redirect("/admin/posts");
    }

    /**
     * Edit a post.
     *
     * @param Request $request
     * @param Response $response
     * @param string $id
     * @return Response
     */
    public function editPost(
        Request $request,
        Response $response,
        string $id
    ): Response {
        $post = $this->db->findOne(Post::class, "where id = ?", [$id]);

        if (!$post) {
            return $response->redirect("/admin/posts");
        }

        $site_name = $this->db->findOne(Meta::class, "where meta_name = ?", [
            "site_name",
        ]);

        return $response->view("admin/edit-post", [
            "id" => $id,
            "post" => $post,
            "injected_js" => BlockHelper::injectedJs(),
            "injected_css" => BlockHelper::injectedCss(),
            "url" => $request->protocol() . "://" . $request->hostname(),
            "url_without_protocol" => $request->hostname(),
            "blocks" => BlockHelper::editableBlocks($post),
            "block_list" => BlockHelper::list(),
            "site_name" => $site_name?->get("meta_value") ?? "",
        ]);
    }

    /**
     * Delete a post.
     *
     * @param Response $response
     * @param string $id
     * @return Response
     */
    public function deletePost(Response $response, string $id): Response
    {
        $post = $this->db->findOne(Post::class, "where id = ?", [$id]);

        if (!$post) {
            return $response->redirect("/admin/posts");
        }

        $this->db->delete($post);

        return $response->redirect("/admin/posts");
    }

    /**
     * Settings page.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function settings(Request $request, Response $response): Response
    {
        $site_name = $this->db->findOne(
            model: Meta::class,
            query: "where meta_name = ?",
            data: ["site_name"]
        );

        $site_description = $this->db->findOne(
            model: Meta::class,
            query: "where meta_name = ?",
            data: ["site_description"]
        );

        return $response->view("admin/settings", [
            "site_name" => $site_name?->get("meta_value") ?? "",
            "site_description" => $site_description?->get("meta_value") ?? "",
            "url" => $request->protocol() . "://" . $request->hostname(),
            "url_without_protocol" => $request->hostname(),
        ]);
    }
}
