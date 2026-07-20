<?php

namespace Webkul\Account\Exceptions;

use Exception;

/**
 * Raised when a company has no journal of the type an accounting document
 * requires, which usually means its chart of accounts was never set up.
 */
class MissingJournalException extends Exception {}
