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

const richtextField = function (content) {
    return {
        content: content,
        inFocus: false,
        // updatedAt is to force Alpine to
        // rerender on selection change
        updatedAt: Date.now(),
        editor: null,
        showLinkPanel: false,
        linkUrl: '',
        linkTarget: '',

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
                        class: "prose prose-action text-gray-500 max-w-none h-96 overflow-x-auto p-3 rounded-b-md focus:outline-none focus:ring-2 focus:ring-action-500 focus:border-action-500"
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
        },

        closeLinkPanel() {
            this.showLinkPanel = false;
            this.linkUrl = '';
            this.linkTarget = '';
        },

        setLink() {
            console.log(this.linkTarget);
            // const url = window.prompt('URL');
            // url: element.url + '#' + refHandle + ':' + element.id + '@' + element.siteId,
            // produces: https://cheerfully-given-v3.test/shop/test#product:157349@1
            this.editor.chain().focus().setLink({
                href: this.linkUrl,
                target: this.linkTarget ? '_blank' : null
            }).run();

            // After we’ve committed it, close the window and clear the models
            this.closeLinkPanel();
        },
    }
};

export default richtextField;
