import { nodeResolve } from '@rollup/plugin-node-resolve';

export default {
    input: 'src/main.js',
    output: {
        file: 'dist/js/main.js',
        name: 'Market',
        format: 'iife'
    },
    plugins: [nodeResolve()]
};
