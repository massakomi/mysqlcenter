import js from "@eslint/js";
import globals from "globals";
import pluginReact from "eslint-plugin-react";
import { defineConfig } from "eslint/config";

export default defineConfig([
  {
      files: ["**/*.{js,mjs,cjs,jsx}"],
      plugins: { js },
      extends: ["js/recommended"],
      settings: {
          react: {
              version: "19.2.3",
          },
      },
      languageOptions: { globals: globals.browser },
      rules: {
          // Custom rules can be added here
          "no-unused-vars": "warn",
          "no-console": "error",
      },
  },
  pluginReact.configs.flat.recommended,
]);
