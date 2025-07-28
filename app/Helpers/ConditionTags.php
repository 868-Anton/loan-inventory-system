<?php

namespace App\Helpers;

class ConditionTags
{
  /**
   * Get grouped condition tag options for Filament Select component
   */
  public static function grouped(): array
  {
    return [
      '✅ Good Condition' => [
        'returned-no-issues' => 'Returned with no issues',
        'fully-functional' => 'Fully functional',
        'clean-and-intact' => 'Clean and intact',
      ],
      '🧩 Missing Parts' => [
        'missing-accessories' => 'Missing accessories',
        'missing-components' => 'Missing components',
        'incomplete-set' => 'Incomplete set',
        'missing-manual-or-packaging' => 'Missing manual or packaging',
      ],
      '🔨 Physical Damage' => [
        'damaged-cracked' => 'Cracked',
        'damaged-dented' => 'Dented',
        'broken-screen' => 'Broken screen',
        'structural-damage' => 'Structural damage',
      ],
      '🛠 Needs Repair' => [
        'non-functional' => 'Non-functional',
        'requires-maintenance' => 'Requires maintenance',
        'battery-issues' => 'Battery issues',
      ],
      '🧼 Sanitation Issues' => [
        'dirty-needs-cleaning' => 'Dirty, needs cleaning',
        'contaminated' => 'Contaminated',
        'odor-present' => 'Odor present',
      ],
      '⚠️ Other Conditions' => [
        'label-or-seal-removed' => 'Label/seal removed',
        'unauthorized-modification' => 'Unauthorized modification',
        'returned-late' => 'Returned late',
      ],
    ];
  }

  /**
   * Get flat array of all valid condition tags for validation
   */
  public static function flat(): array
  {
    $flat = [];
    foreach (self::grouped() as $group => $options) {
      foreach ($options as $value => $label) {
        $flat[] = $value;
      }
    }
    return $flat;
  }

  /**
   * Check if a tag belongs to the "Good Condition" category
   */
  public static function isGoodTag(string $tag): bool
  {
    $goodConditionTags = [
      'returned-no-issues',
      'fully-functional',
      'clean-and-intact',
    ];

    return in_array($tag, $goodConditionTags);
  }

  /**
   * Get all good condition tags
   */
  public static function getGoodTags(): array
  {
    return [
      'returned-no-issues',
      'fully-functional',
      'clean-and-intact',
    ];
  }

  /**
   * Get all issue tags (non-good condition)
   */
  public static function getIssueTags(): array
  {
    return array_diff(self::flat(), self::getGoodTags());
  }

  /**
   * Check if tags array contains any good condition tags
   */
  public static function hasGoodTags($tags): bool
  {
    // Handle both string and array inputs
    if (is_string($tags)) {
      $decoded = json_decode($tags, true);
      $tags = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($tags)) {
      return false;
    }

    return !empty(array_intersect($tags, self::getGoodTags()));
  }

  /**
   * Check if tags array contains any issue tags
   */
  public static function hasIssueTags($tags): bool
  {
    // Handle both string and array inputs
    if (is_string($tags)) {
      $decoded = json_decode($tags, true);
      $tags = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($tags)) {
      return false;
    }

    return !empty(array_intersect($tags, self::getIssueTags()));
  }

  /**
   * Filter tags to keep only good condition tags (for exclusivity)
   */
  public static function filterToGoodTags($tags): array
  {
    // Handle both string and array inputs
    if (is_string($tags)) {
      $decoded = json_decode($tags, true);
      $tags = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($tags)) {
      return [];
    }

    return array_values(array_intersect($tags, self::getGoodTags()));
  }

  /**
   * Format tags for display (convert to readable text)
   */
  public static function formatForDisplay($tags): string
  {
    // Handle both string and array inputs
    if (is_string($tags)) {
      // Try to decode JSON string, fallback to empty array
      $decoded = json_decode($tags, true);
      $tags = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($tags) || empty($tags)) {
      return 'No tags';
    }

    return collect($tags)->map(function ($tag) {
      // Convert tag format to readable text
      $parts = explode('.', $tag);
      if (count($parts) === 2) {
        $category = ucwords(str_replace('_', ' ', $parts[0]));
        $condition = ucwords(str_replace('-', ' ', $parts[1]));
        return "{$category}: {$condition}";
      }
      return ucwords(str_replace(['-', '_'], ' ', $tag));
    })->join(', ');
  }
}
