# Contributing

First off, thank you for considering contributing to Laravel Event Sourcing Generator! Your contributions are greatly
appreciated and help make this project better for everyone.

The following guidelines are here to ensure the contribution
process runs smoothly.

## How Can I Contribute?

### 1. Reporting Bugs

If you find a bug, please:

- Check the [issue tracker](https://github.com/albertoarena/laravel-event-sourcing-generator/issues) to see if it has
  already been reported.
- Open a new issue if it hasn't been reported.
    - Use a clear and descriptive title.
    - Include steps to reproduce the issue, expected behavior, and actual behavior.
    - Provide any relevant logs, screenshots, or code snippets.

### 2. Suggesting Features

We welcome feature requests! To suggest a feature:

- Check the [issue tracker](https://github.com/albertoarena/laravel-event-sourcing-generator/issues) to see if the
  feature has already been
  suggested.
- Open a new issue labeled `feature request`.
    - Clearly explain the feature and why it would be beneficial.
    - Include examples or use cases if possible.

### 3. Submitting Code Changes

Want to fix a bug or implement a feature? Great! Here’s how to contribute code:

1. **Fork** the repository.
2. Create a new **branch** for your work:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Make your changes** and ensure your code adheres to the project’s style guidelines.
   ```bash
   # Run Laravel Pint
   composer fix
   # Run LaraStan analysis
   composer static
   ```
4. **Test your changes** to make sure everything works as expected.
   ```bash
   composer test
   ```
5. Refresh code coverage badge icon
   ```bash
   composer test-coverage
   ```
6. **Commit** your changes with a clear and concise commit message.
7. **Push** your branch to your forked repository:
   ```bash
   git push origin feature/your-feature-name
   ```
8. Open a **pull request (PR)** to the main repository:
    - Provide a detailed description of your changes.
    - Reference any related issues.
    - Wait for feedback or approval from the maintainers.

### Improving Documentation

If you find areas in the documentation that can be improved:

- Open an issue to discuss your proposed changes.
- Submit a pull request with your updates.

## Etiquette

To maintain a welcoming and collaborative environment:

- **Be respectful:** Treat everyone with kindness and respect, even when there are disagreements.
- **Be constructive:** Provide helpful, actionable feedback. Avoid harsh criticism.
- **Be inclusive:** Encourage diverse perspectives and ensure your contributions are accessible to everyone.
- **Be patient:** Remember that maintainers are volunteers and may not respond immediately. Allow time for reviews and
  discussions.
- **Acknowledge contributions:** Give credit to other contributors where applicable.

## Code Guidelines

- This project uses [Laravel Pint](https://laravel.com/docs/11.x/pint) and its code is analysed
  using [LaraStan](https://github.com/larastan/larastan).
- Write clear, maintainable, and well-documented code.
- Ensure your code passes all tests and adheres to the project’s formatting rules.

## Getting Help

If you need help, feel free to:

- Ask a question by opening an issue.
- [Contact the main developer](https://github.com/albertoarena/)

Thank you for contributing! Your support helps keep this project thriving.