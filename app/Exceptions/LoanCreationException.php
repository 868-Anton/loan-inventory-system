<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for loan creation errors
 */
class LoanCreationException extends Exception
{
  /**
   * Error codes for specific loan creation issues
   */
  public const ERROR_INVALID_BORROWER = 1001;
  public const ERROR_INVALID_ITEMS = 1002;
  public const ERROR_INSUFFICIENT_QUANTITY = 1003;
  public const ERROR_INVALID_SERIAL_NUMBERS = 1004;
  public const ERROR_ITEM_ALREADY_BORROWED = 1005;
  public const ERROR_MISSING_REQUIRED_DATA = 1006;
  public const ERROR_VOUCHER_GENERATION_FAILED = 1007;

  /**
   * Create a new exception for invalid borrower data
   * 
   * @param string $message The error message
   * @return self
   */
  public static function invalidBorrower(string $message = 'Invalid borrower data provided'): self
  {
    return new self($message, self::ERROR_INVALID_BORROWER);
  }

  /**
   * Create a new exception for invalid items data
   * 
   * @param string $message The error message
   * @return self
   */
  public static function invalidItems(string $message = 'Invalid items data provided'): self
  {
    return new self($message, self::ERROR_INVALID_ITEMS);
  }

  /**
   * Create a new exception for insufficient item quantity
   * 
   * @param string $itemName The name of the item with insufficient quantity
   * @param int $requested The quantity requested
   * @param int $available The quantity available
   * @return self
   */
  public static function insufficientQuantity(string $itemName, int $requested, int $available): self
  {
    return new self(
      "Insufficient quantity for item '{$itemName}'. Requested: {$requested}, Available: {$available}",
      self::ERROR_INSUFFICIENT_QUANTITY
    );
  }

  /**
   * Create a new exception for invalid serial numbers
   * 
   * @param string $message The error message
   * @return self
   */
  public static function invalidSerialNumbers(string $message = 'Invalid serial numbers provided'): self
  {
    return new self($message, self::ERROR_INVALID_SERIAL_NUMBERS);
  }

  /**
   * Create a new exception for items that are already borrowed
   * 
   * @param string $itemName The name of the already borrowed item
   * @return self
   */
  public static function itemAlreadyBorrowed(string $itemName): self
  {
    return new self(
      "Item '{$itemName}' is already borrowed and cannot be loaned again",
      self::ERROR_ITEM_ALREADY_BORROWED
    );
  }

  /**
   * Create a new exception for missing required data
   * 
   * @param string $fieldName The name of the missing field
   * @return self
   */
  public static function missingRequiredData(string $fieldName): self
  {
    return new self(
      "Required data missing: {$fieldName}",
      self::ERROR_MISSING_REQUIRED_DATA
    );
  }

  /**
   * Create a new exception for voucher generation failure
   * 
   * @param string $message The error message
   * @return self
   */
  public static function voucherGenerationFailed(string $message = 'Failed to generate loan voucher'): self
  {
    return new self($message, self::ERROR_VOUCHER_GENERATION_FAILED);
  }
}
