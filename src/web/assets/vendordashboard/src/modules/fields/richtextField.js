import { Editor as TipTap } from "@tiptap/core";
import Document from '@tiptap/extension-document';
import Text from '@tiptap/extension-text';
import HardBreak from '@tiptap/extension-hard-break';
import Typography from '@tiptap/extension-typography';
import History from '@tiptap/extension-history';

import Heading from '@tiptap/extension-heading';
import Paragraph from '@tiptap/extension-paragraph';
import Bold from '@tiptap/extension-bold';
import Italic from '@tiptap/extension-italic';
import BulletList from '@tiptap/extension-bullet-list';
import OrderedList from '@tiptap/extension-ordered-list';
import ListItem from '@tiptap/extension-list-item';
import Blockquote from '@tiptap/extension-blockquote';

import Link from '@tiptap/extension-link';

const richtextField = function (content, fieldId, heightClass) {
    return {
        fieldId: fieldId,
        content: content,
        inFocus: false,
        // updatedAt is to force Alpine to
        // rerender on selection change
        updatedAt: Date.now(),
        editor: null,
        showLinkPanel: false,
        linkUrl: '',
        linkTarget: '',
        heightClass: heightClass,

        init(el) {
            let editor = new TipTap({
                element: el,
                extensions: [
                    Document,
                    Text,
                    HardBreak,
                    Typography,
                    History,
                    Paragraph,
                    Heading.configure({
                        levels: [2, 3],
                    }),
                    Bold,
                    Italic,
                    BulletList,
                    OrderedList,
                    ListItem,
                    Blockquote,
                    Link.configure({
                        openOnClick: false
                    }),
                ],
                content: this.content,
                editorProps: {
                    attributes: {
                        class: "prose prose-action text-gray-500 max-w-none "+this.heightClass+" overflow-x-auto p-3 rounded-b-md focus:outline-none focus:ring-2 focus:ring-action-500 focus:border-action-500"
                    }
                }
            });

            editor.on("update", () => {
                this.content = this.editor.getHTML();
            });

            editor.on("focus", () => {
                this.inFocus = true;
            });

            editor.on("selection", () => {
                this.updatedAt = Date.now();
            });

            this.editor = editor;
        },

        openLinkPanel() {
            this.showLinkPanel = true;

            // Preset the form with the current link data if its there
            const attrs = this.editor.getMarkAttributes('link');
            if (attrs.href) {
                this.linkUrl = attrs.href;
            }
            if (attrs.target && attrs.target === '_blank') {
                this.linkTarget = true;
            }
        },

        closeLinkPanel() {
            this.showLinkPanel = false;
            this.linkUrl = '';
            this.linkTarget = '';
        },

        setLink() {
            this.editor.chain().focus().setLink({
                href: this.linkUrl,
                target: this.linkTarget ? '_blank' : null
            }).run();

            // After we’ve committed it, close the window and clear the models
            this.closeLinkPanel();
        },

        fixScroll(evt) {
            // Product queries
            if (evt.target.id === 'field-'+this.fieldId+'-link-panel-link-product-results') {

                // Hide file results
                const fileResults = document.getElementById('field-'+this.fieldId+'-link-panel-link-file-results-inner');
                if (fileResults) {
                    fileResults.remove();
                }

                // Sort the scroll
                htmx.find('#field-'+this.fieldId+'-link-panel-container').scrollTop = 240;
            }

            // File queries
            if (evt.target.id === 'field-'+this.fieldId+'-link-panel-link-file-results') {

                // Hide file results
                const productResults = document.getElementById('field-'+this.fieldId+'-link-panel-link-product-results-inner');
                if (productResults) {
                    productResults.remove();
                }

                // Sort the scroll
                htmx.find('#field-'+this.fieldId+'-link-panel-container').scrollTop = 150;
            }
        }
    }
};

export default richtextField;
