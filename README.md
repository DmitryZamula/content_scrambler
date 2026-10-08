# Content Scrambler

Overwrites stored text content with Lorem Ipsum **of the same length** and can
swap the site logo. Useful for preparing demo sites, screenshots and
development copies of a site.

HTML tags and entities are preserved (only the visible text is replaced). All
translations are processed. The current revision is overwritten, no new
revisions are created.

> **Warning:** the conversion is irreversible. Use it only on development or
> demo copies of a site and make a database backup first. Never run it on
> production.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/content_scrambler).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/content_scrambler).

## Table of contents

- Requirements
- Installation
- Configuration
- Supported content
- Limitations
- Maintainers

## Requirements

- Drupal 11.1 or later (uses object-oriented hook implementations)
- Core module: File
- Optional: [Paragraphs](https://www.drupal.org/project/paragraphs)

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

```
composer require drupal/content_scrambler
drush en content_scrambler
```

## Configuration

1. Grant the permission **Administer Content Scrambler** (restricted
   permission) to the appropriate role.
2. Go to *Administration > Configuration > Development > Content Scrambler*.
3. **By content types:** tick the fields to convert for each entity type and
   bundle and press *Convert selected*, or press *Convert ALL text fields on
   the site*.
4. **By field:** choose an entity type, enter a bundle and a field machine
   name and press *Convert*.
5. **Logo:** upload a replacement logo. It is swapped in the browser using the
   CSS selector set on the same form (the default covers Olivero and most
   themes).

## Supported content

- Entity types: content, taxonomy terms, custom blocks, media, comments and
  paragraphs.
- Field types: Text (plain), Text (plain, long), Text (formatted), Text
  (formatted, long), Text (formatted, long, with summary) and Link (the link
  title is converted).
- Base fields: content title, term name and description, block description,
  media name and comment subject.

## Limitations

- Users, e-mail addresses, file names, images and configuration are **not**
  changed. This module does not anonymize personal data; it is not a
  GDPR/privacy tool.
- Path aliases generated earlier (for example by Pathauto) are not regenerated.

## Maintainers

- Your Name - [your-drupal-org-username](https://www.drupal.org/u/your-drupal-org-username)
