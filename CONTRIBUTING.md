# Contributing to Tarifador - Call Accounting for FreePBX

First off, thank you for considering contributing to this project! We're thrilled that you're interested in helping us make it better. Every contribution, no matter how small, is valuable.

This is an open-source project, and we welcome any contribution, from reporting a bug to submitting a pull request.

## How Can I Contribute?

### Helping with Translations
We welcome contributions to translate the module into new languages. The process follows the standard gettext workflow.

1.  **Find the Template:** The main template file containing all translatable strings is located at `i18n/tarifador.pot`.

2.  **Create Your Language File:** To start a new translation for a language (e.g., French fr_FR), copy the .pot file to a new location: `i18n/fr_FR/LC_MESSAGES/tarifador.po`.

3.  **Translate:** Use a .po file editor like Poedit to translate the strings from English (msgid) to your target language (msgstr).

4.  **Submit a Pull Request:** Once your translation is complete, save the file (which should also generate the binary .mo file) and submit a pull request with the new `.po` and `.mo` files included.

### Reporting Bugs
If you find a bug, please ensure it hasn't already been reported by searching the [Issues](https://github.com/mrpbueno/tarifador/issues) on GitHub.

If you can't find an open issue addressing the problem, please [open a new one](https://github.com/mrpbueno/tarifador/issues/new). Be sure to include:
- A **clear and descriptive title**.
- A **detailed description** of the problem, including steps to reproduce it.
- The **expected behavior** versus the **actual behavior**.
- Your environment details (e.g., FreePBX version, module version, Asterisk version).

### Suggesting Enhancements
If you have an idea for an enhancement, feel free to open an issue to discuss it. Please provide:
- A **clear and descriptive title**.
- A detailed explanation of the proposed enhancement and the problem it solves.
- Any relevant context or examples that might help us understand the suggestion.

### Submitting Pull Requests
If you have a bug fix or a new feature you'd like to contribute, please follow these steps:

1.  **Fork the repository** to your own GitHub account.
2.  **Create a new branch** for your changes:
    ```bash
    git checkout -b feature/my-amazing-feature
    ```
3.  **Make your changes** in the new branch.
4.  **Commit your changes** with a clear and descriptive commit message:
    ```bash
    git commit -am "feat: Add some amazing feature"
    ```
5.  **Push your branch** to your fork:
    ```bash
    git push origin feature/my-amazing-feature
    ```
6.  **Open a Pull Request** back to the main repository. In the PR description, please explain the "why" and "what" of your changes. If your PR addresses an open issue, please reference it (e.g., "Closes #123").

## Coding Conventions
To keep the codebase consistent and easy to maintain, please follow these guidelines:

- **Language:** All variables, methods, comments, and commit messages must be in **English**.
- **Internationalization (i18n):** All user-facing strings must be wrapped in the `_()` gettext function to allow for translation.
- **Code Style:** Follow the general coding style of the existing files. Write clean, readable, and well-commented code, especially for complex logic inside Traits (`CallTrait`, `RateTrait`).
- **FreePBX BMO Practices:** Ensure your code adheres to the BMO (Big Module Object) architecture used throughout the module.

Thank you again for your interest in making Tarifador better!