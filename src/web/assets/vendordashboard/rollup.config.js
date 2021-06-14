import { babel } from '@rollup/plugin-babel';
import { nodeResolve } from '@rollup/plugin-node-resolve';
import { uglify } from "rollup-plugin-uglify";

export default {
    input: 'src/main.js',
    output: {
        file: 'dist/js/main.js',
        name: 'Market',
        format: 'iife',
        sourcemap: true,
    },
    plugins: [
        nodeResolve(),
        babel({
            babelHelpers: 'bundled',
            exclude: 'node_modules/**',
        }),
        uglify(),
    ]
};
