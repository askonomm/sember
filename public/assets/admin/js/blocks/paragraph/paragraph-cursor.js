import {LitElement, html, css} from 'https://cdn.jsdelivr.net/gh/lit/dist@3/core/lit-core.min.js';

export class ParagraphCursor extends LitElement {
  static properties = {}

  static styles = css`
      @keyframes blink {
          0% {
              opacity: 1;
          }
          50% {
              opacity: 0;
          }
          100% {
              opacity: 1;
          }
      }
      
      span {
          display: inline-block;
          height: 100%;
          width: 1px;
          background: #111;
          position: absolute;
          animation: blink 1s infinite;
          margin-top: 2px;
      }
  `;

  constructor() {
    super();
  }

  render() {
    return html`<span></span>`;
  }
}

customElements.define('paragraph-cursor', ParagraphCursor);