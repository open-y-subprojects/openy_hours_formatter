<?php

namespace Drupal\Tests\openy_hours_formatter\Unit;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the openy_hours tokens used for Schema.org OpeningHoursSpecification.
 *
 * @group openy_hours_formatter
 */
class HoursTokensTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 3) . '/openy_hours_formatter.module';
  }

  /**
   * Tests that opens/closes are parsed and the lists stay aligned.
   *
   * @dataProvider hoursProvider
   */
  public function testHoursTokens(string $hours, string $expected_opens, string $expected_closes): void {
    $node = $this->createNode(array_fill_keys(
      ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
      $hours
    ));
    $tokens = [
      'day_of_week' => '[openy_hours:day_of_week]',
      'opens' => '[openy_hours:opens]',
      'closes' => '[openy_hours:closes]',
    ];

    $replacements = openy_hours_formatter_tokens(
      'openy_hours',
      $tokens,
      ['node' => $node],
      [],
      new BubbleableMetadata()
    );

    $opens = explode(',', $replacements['[openy_hours:opens]']);
    $closes = explode(',', $replacements['[openy_hours:closes]']);
    $days = explode(',', $replacements['[openy_hours:day_of_week]']);
    $this->assertCount(7, $days);
    $this->assertSame(array_fill(0, 7, $expected_opens), $opens);
    $this->assertSame(array_fill(0, 7, $expected_closes), $closes);
  }

  /**
   * Data provider for testHoursTokens().
   */
  public static function hoursProvider(): array {
    return [
      'compact' => ['5:30AM-8PM', '05:30', '20:00'],
      'spaces and lowercase' => ['8am - 4pm', '08:00', '16:00'],
      'periods' => ['8 a.m. - 4 p.m.', '08:00', '16:00'],
      'en dash' => ["8AM\u{2013}4PM", '08:00', '16:00'],
      'the word to' => ['8AM to 4PM', '08:00', '16:00'],
      '24 hour' => ['8:00-16:00', '08:00', '16:00'],
      'trailing text' => ['8AM-4PM Closed July 4th', '08:00', '16:00'],
      'closed' => ['Closed', '00:00', '00:00'],
      'empty' => ['', '00:00', '00:00'],
    ];
  }

  /**
   * Tests that an empty day keeps the opens/closes lists aligned.
   */
  public function testEmptyDayKeepsListsAligned(): void {
    $node = $this->createNode([
      'mon' => '9AM-5PM',
      'tue' => '',
      'wed' => '9AM-5PM',
      'thu' => '9AM-5PM',
      'fri' => '9AM-5PM',
      'sat' => '9AM-5PM',
      'sun' => '9AM-5PM',
    ]);

    $replacements = openy_hours_formatter_tokens(
      'openy_hours',
      ['opens' => '[openy_hours:opens]', 'closes' => '[openy_hours:closes]'],
      ['node' => $node],
      [],
      new BubbleableMetadata()
    );

    $this->assertSame(
      ['09:00', '00:00', '09:00', '09:00', '09:00', '09:00', '09:00'],
      explode(',', $replacements['[openy_hours:opens]'])
    );
    $this->assertSame(
      ['17:00', '00:00', '17:00', '17:00', '17:00', '17:00', '17:00'],
      explode(',', $replacements['[openy_hours:closes]'])
    );
  }

  /**
   * Creates a minimal stub node exposing field_branch_hours.
   *
   * @param array $hours
   *   Hours text keyed by the day abbreviation (mon, tue, ...).
   *
   * @return object
   *   The stub node.
   */
  protected function createNode(array $hours): object {
    $field = new class($hours) {

      /**
       * Constructs the stub field item.
       */
      public function __construct(array $hours) {
        foreach ($hours as $day => $value) {
          $this->{'hours_' . $day} = $value;
        }
      }

      /**
       * Mimics FieldItemListInterface::isEmpty().
       */
      public function isEmpty(): bool {
        return FALSE;
      }

    };

    return new class($field) {

      /**
       * The stub field, named after the Drupal field it mimics.
       */
      // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
      public object $field_branch_hours;

      /**
       * Constructs the stub node.
       */
      public function __construct(object $field) {
        $this->field_branch_hours = $field;
      }

      /**
       * Mimics FieldableEntityInterface::hasField().
       */
      public function hasField(string $name): bool {
        return $name === 'field_branch_hours';
      }

      /**
       * Mimics FieldableEntityInterface::get().
       */
      public function get(string $name): object {
        return $this->field_branch_hours;
      }

    };
  }

}
