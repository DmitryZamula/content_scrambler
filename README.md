# Content Scrambler

Do your developers work with a copy of the production database?
Does that database contain unpublished, client-owned or otherwise sensitive
content? Do local or team environments, staging servers and demo sites need
to look real, but must not expose the real text?

Then Content Scrambler is for you.

Content Scrambler helps you anonymize the content of local and shared
development copies of a site. It overwrites stored text (titles, bodies, term
names, blocks, comments, paragraphs and more) with Lorem Ipsum of the **same
length**. HTML tags and entities are preserved, so pages keep their real
structure and layout, but the original wording is gone.

> **Warning:** the conversion is irreversible. The only way back is to restore
> a backup. Use it only on development, staging or demo copies. Never run it
> on production.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/content_scrambler).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/content_scrambler).

## Typical use cases

- A team member needs a local copy of the site, but the content must stay
  confidential.
- Preparing a staging or demo site for a client or a conference.
- Taking screenshots or recording a demo without exposing real content.
- Handing a site copy to an external agency or a freelancer.

## Features

- Choose entity types, bundles and fields in a form, or convert every
  supported text field with one button.
- Convert a single field by its machine name.
- Replacement text is configurable (Lorem Ipsum by default).
- Supports content, taxonomy terms, custom blocks, media, comments and
  Paragraphs.
- Supports plain and formatted text fields (including summaries) and link
  titles.
- Keeps HTML markup and processes all translations.
- Overwrites the current revision, no new revisions are created.
- Uses the Batch API, so large sites work.
- Replaces the site logo with a neutral image, so the brand is not exposed
  either.

## Table of contents

- Typical use cases
- Features
- Requirements
- Installation
- Configuration
- Supported content
- Limitations
- Similar projects
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
5. **Replacement text:** the text that replaces the original content. It
   defaults to the standard Lorem Ipsum and can be changed on the same form
   (it is cut to the length of the original and repeated if needed).
6. **Logo:** upload a replacement logo. It is swapped in the browser using the
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

- Content Scrambler changes stored text only. Users, e-mail addresses, file
  names, images and configuration are **not** changed, and it is not a
  complete GDPR or privacy solution. Combine it with `drush sql:sanitize` (or
  a similar tool) for user data.
- Path aliases generated earlier (for example by Pathauto) are not regenerated.

## Similar projects

- [Scrambler](https://www.drupal.org/project/scrambler): shuffles characters
  or words of selected fields.
- [Devel](https://www.drupal.org/project/devel) (Devel Generate): creates new
  dummy content instead of rewriting existing content.

## Maintainers

- Dmytro Zamula - [GitHub](https://github.com/DmitryZamula/content_scrambler)
