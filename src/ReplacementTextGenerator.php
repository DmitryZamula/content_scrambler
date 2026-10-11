<?php

declare(strict_types=1);

namespace Drupal\content_scrambler;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Generates replacement text of the same length as the original text.
 */
class ReplacementTextGenerator {

  /**
   * Neutral text used only if the configured replacement text is empty.
   */
  protected const FALLBACK = 'Text ';

  /**
   * The resolved source text (cached for the lifetime of the service).
   */
  protected ?string $source = NULL;

  public function __construct(protected ConfigFactoryInterface $configFactory) {}

  /**
   * Returns the text used as the source of replacements.
   *
   * Uses the replacement text from the settings (the module ships with a
   * Lorem Ipsum default in its install configuration). Whitespace is
   * normalized and a trailing space is added so the text can be repeated
   * seamlessly.
   */
  public function getSource(): string {
    if ($this->source === NULL) {
      $custom = (string) $this->configFactory->get('content_scrambler.settings')->get('source_text');
      $custom = trim((string) preg_replace('/\s+/u', ' ', $custom));
      $this->source = $custom !== '' ? $custom . ' ' : self::FALLBACK;
    }
    return $this->source;
  }

  /**
   * Returns replacement text of exactly $length characters.
   *
   * @param int $length
   *   Desired length in characters.
   * @param int $offset
   *   Position in the (endlessly repeated) source; advanced by the call so
   *   consecutive chunks continue the text.
   */
  public function generate(int $length, int &$offset = 0): string {
    if ($length <= 0) {
      return '';
    }
    $source = $this->getSource();
    $size = mb_strlen($source);
    $offset %= $size;

    // Do not start a chunk with whitespace.
    while (trim(mb_substr($source, $offset, 1)) === '') {
      $offset = ($offset + 1) % $size;
    }

    $repeats = (int) ceil(($offset + $length) / $size) + 1;
    $text = mb_substr(str_repeat($source, $repeats), $offset, $length);
    $offset = ($offset + $length) % $size;

    // Do not end a chunk with whitespace (keeps the exact length).
    if (preg_match('/\s$/u', $text)) {
      $text = mb_substr($text, 0, -1) . 'a';
    }
    return $text;
  }

  /**
   * Converts plain text; keeps line breaks and surrounding whitespace.
   */
  public function plain(string $original): string {
    if (trim($original) === '') {
      return $original;
    }
    $offset = 0;
    $parts = preg_split('/(\R+)/u', $original, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $i => $part) {
      if ($i % 2 === 0) {
        $parts[$i] = $this->replaceSegment($part, $offset);
      }
    }
    return implode('', $parts);
  }

  /**
   * Converts HTML; keeps tags, comments and entities, replaces only text.
   */
  public function html(string $original): string {
    if (trim($original) === '') {
      return $original;
    }
    $offset = 0;
    $pattern = '/(<!--.*?-->|<[^>]+>|&(?:#\d+|#x[0-9a-f]+|[a-z][a-z0-9]*);)/isu';
    $parts = preg_split($pattern, $original, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === FALSE) {
      return $this->plain($original);
    }
    foreach ($parts as $i => $part) {
      // Odd indexes are the captured tags/entities - leave them untouched.
      if ($i % 2 === 0) {
        $parts[$i] = $this->replaceSegment($part, $offset);
      }
    }
    return implode('', $parts);
  }

  /**
   * Replaces one text segment, preserving leading/trailing whitespace.
   */
  protected function replaceSegment(string $segment, int &$offset): string {
    if (trim($segment) === '') {
      return $segment;
    }
    preg_match('/^(\s*)(.*?)(\s*)$/su', $segment, $m);
    return $m[1] . $this->generate(mb_strlen($m[2]), $offset) . $m[3];
  }

}
