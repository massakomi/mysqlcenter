const path = require('path');
module.exports = {
    entry: './js/index.js',
    mode: 'development',
    output: {
        filename: 'dist.js',
        path: path.resolve(__dirname, 'js'),
    },
    cache: false,
    module: {
        rules: [
            {
                test: /\.(?:js|mjs|cjs)$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        targets: "defaults",
                        presets: [
                            ['@babel/preset-env'],
                            ['@babel/preset-react']
                        ]
                    }
                }
            }
        ]
    },
};