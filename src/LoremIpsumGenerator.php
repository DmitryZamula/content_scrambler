<?php

declare(strict_types=1);

namespace Drupal\content_scrambler;

/**
 * Generates Lorem Ipsum text of the same length as the original text.
 */
class LoremIpsumGenerator {

  /**
   * Source text, repeated as needed.
   */
  protected const SOURCE = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. ';

  /**
   * Returns Lorem Ipsum of exactly $length characters.
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
    $source = self::SOURCE;
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
