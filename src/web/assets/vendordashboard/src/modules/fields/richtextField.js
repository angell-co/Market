import { Editor as TipTap } from "@tiptap/core";
import Document from '@tiptap/extension-document';
import Text from '@tiptap/extension-text';
import HardBreak from '@tiptap/extension-hard-break';
import Typography from '@tiptap/extension-typography';

import Heading from '@tiptap/extension-heading';
import Paragraph from '@tiptap/extension-paragraph';
import Bold from '@tiptap/extension-bold';
import Italic from '@tiptap/extension-italic';
import BulletList from '@tiptap/extension-bullet-list';
import OrderedList from '@tiptap/extension-ordered-list';
import ListItem from '@tiptap/extension-list-item';
import Blockquote from '@tiptap/extension-blockquote';

const richtextField = function (content) {
    return {
        content: content,
        inFocus: false,
        // updatedAt is to force Alpine to
        // rerender on selection change
        updatedAt: Date.now(),
        editor: null,

        init(el) {
            let editor = new TipTap({
                element: el,
                extensions: [
                    Document,
                    Text,
                    HardBreak,
                    Typography,
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
                ],
                content: this.content,
                editorProps: {
                    attributes: {
                        class: "prose prose-action text-gray-500 max-w-none"
                    }
                }
            })

            editor.on("update", () => {
                this.content = this.editor.getHTML()
            })

            editor.on("focus", () => {
                this.inFocus = true
            })

            editor.on("selection", () => {
                this.updatedAt = Date.now()
            })

            this.editor = editor
        }
    }
};

export default richtextField;
