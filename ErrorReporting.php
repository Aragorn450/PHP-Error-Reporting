<?php

/**
 * PHP Custom Error Handler
 * 
 * @package ErrorHandler
 * @author James McFall <james@mcfall.geek.nz>
 * @author Charlie Lehardy <charlie.lehardy@gmail.com>
 */

// Set the default error handler for php to this class
set_error_handler('ErrorHandler::handleError');
register_shutdown_function('ErrorHandler::shutdownHandler');

# @todo - Don't forget to set error numb threashold
# @todo - Log to screen in certain environments. Make so Bootstrapper can set up.
# @todo -  Set default error log logation to apache's error log I guess


/**
 * This class can be used to overwrite the default error handling behaviour 
 * of php and provide a more comprehensive error message 
 * 
 * @version 0.2
 * @author James McFall <james@mcfall.geek.nz>
 * @author Charlie Lehardy <charlie.lehardy@gmail.com>
 */
class ErrorHandler {
    
    # Set up logging locations
    public static $logViaFile   = true;
    public static $logViaEmail  = true;
    public static $logViaScreen = true;
    
    # Error logging timezone
    public static $errorLogTimezone = "America/Phoenix";
    
    # Set who the error emails should be sent to
    public static $emailTo = "charlie.lehardy@gmail.com";
    
    # Set error log file location 
    public static $logFileLocation = "/tmp/php_error_handler/errors.log";
    
    /**
     * This method is the main method that handles all of the functionality for
     * the handler.
     * 
     * @param int $errorCode - Level of the error raised.
     * @param string $message - Error message.
     * @param string $file - File of origin the error occurred in.
     * @param int $lineNumber - Line number the error occured on in the file of origin.
     * @param array $vars - A large array of all variables that exist in scope at the time of the error.
     */
    public static function handleError($errorCode, $message, $file, $lineNumber, $vars = new Array()) {
        # Send out email error notification.
        if (self::$logViaEmail)
            self::sendErrorEmail($errorCode, $message, $file, $lineNumber, $vars);
        
        # Log the error to a file.
        if (self::$logViaFile)
            self::writeToErrorLog($errorCode, $message, $file, $lineNumber, $vars);
        
        # Log the error to the screen
        if (self::$logViaScreen) {
            self::logErrorToScreen($errorCode, $message, $file, $lineNumber, $vars);
        }
    }
    
    /**
     * This method catches a shutdown of PHP and if it's because an E_ERROR was thrown, it
     * will send the message to the error handler.
     * 
     * @uses ErrorHandler::handleError()
     */
    public static function handleShutdown() {
        $last_error = error_get_last();
        
        // Check if the last error is a fatal error.
        if ($last_error['type'] === E_ERROR) {
            self::handleError(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
        }
    }
    
    /**
     * This function can be used to overwrite the default error handling behaviour 
     * of php and provide a more comprehensive error message.
     * 
     * This gives a large amount of data around the error as it can easily be fit
     * into an email message.
     * 
     * @param int $errorCode - Level of the error raised.
     * @param string $message - Error message.
     * @param string $file - File of origin the error occurred in.
     * @param int $lineNumber - Line number the error occured on in the file of origin.
     * @param array $vars - A large array of all variables that exist in scope at the time of the error.
     */
    static function sendErrorEmail($errorCode, $message, $file, $lineNumber, $vars) {
        $timezone = new DateTimeZone(self::$errorLogTimezone);
        $time = new DateTime("now", $timezone);
        
        # Start building an error message
        $errorMessage = "<p><h2>An unhandled error occurred on http://" . $_SERVER['HTTP_HOST'] . "</h2></p>";

        # Basic Error Details
        $errorMessage .= "<p>";
        $errorMessage .= "<b>Time Of Occurance</b>: " . $time->format('h:i:sa d M Y') . "<br />";
        $errorMessage .= "<b>Error Number</b>: $errorCode<br />";
        $errorMessage .= "<b>Line Number</b>: $lineNumber<br />";
        $errorMessage .= "<b>File Name</b>: $file<br />";
        $errorMessage .= "<b>Error</b>: $message<br />";
        $errorMessage .= "</p>";

        # Build Debug Backtrace
        $backtrace = "<pre>" . print_r(debug_backtrace(), 1) . "</pre>";

        # The current vars available at the time
        $currentVars = "<pre>" . print_r($vars, 1) . "</pre>";

        # Append additional details to error message
        $errorMessage .= "<hr /><p><b>Debug Backtrace</b></p>" . $backtrace;
        $errorMessage .= "<hr /><p><b>Current In Scope Variables</b></p>" . $currentVars;

        $headers = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

        error_log($errorMessage, 1, self::$emailTo, $headers);

    }
    
    /**
     * This method writes the error message to the log file if it's available.
     * 
     * @param int $errorCode - Level of the error raised.
     * @param string $message - Error message.
     * @param string $file - File of origin the error occurred in.
     * @param int $lineNumber - Line number the error occured on in the file of origin.
     * @param array $vars - A large array of all variables that exist in scope at the time of the error.
     */
    public static function writeToErrorLog($errorCode, $message, $file, $lineNumber, $vars) {
        $timezone = new DateTimeZone(self::$errorLogTimezone);
        $time = new DateTime("now", $timezone);
        
        # Start building an error message
        $errorMessage = $time->format('h:i:sa d M Y') .": An unhandled error($errorCode) ";
        $errorMessage .= "occurred on http://" . $_SERVER['HTTP_HOST'];
        $errorMessage .= " in $file on line $lineNumber - $message";
        
        # Prepend with new line
        $errorMessage = "\n" . $errorMessage;
        
        # Write to log file
        error_log($errorMessage, 3, self::$logFileLocation);
    }
    
    /**
     *
     * @param int $number - Level of the error raised.
     * @param string $message - Error message.
     * @param string $file - File of origin the error occurred in.
     * @param int $lineNumber - Line number the error occured on in the file of origin.
     * @param array $vars - A large array of all variables that exist in scope at the time of the error.
     */
    public static function logErrorToScreen($number, $message, $file, $lineNumber, $vars) {
        $timezone = new DateTimeZone(self::$errorLogTimezone);
        $time = new DateTime("now", $timezone);
        
        # Start building an error message
        $errorMessage = "<p><h2>An unhandled error occurred on http://" . $_SERVER['HTTP_HOST'] . "</h2></p>";

        # Basic Error Details
        $errorMessage .= "<p>";
        $errorMessage .= "<b>Time Of Occurance</b>: " . $time->format('h:i:sa d M Y') . "<br />";
        $errorMessage .= "<b>Error Number</b>: $number<br />";
        $errorMessage .= "<b>Line Number</b>: $lineNumber<br />";
        $errorMessage .= "<b>File Name</b>: $file<br />";
        $errorMessage .= "<b>Error</b>: $message<br />";
        $errorMessage .= "</p>";

        # Build Debug Backtrace
        $backtrace = "<pre>" . print_r(debug_backtrace(), 1) . "</pre>";

        # The current vars available at the time
        $currentVars = "<pre>" . print_r($vars, 1) . "</pre>";

        # Append additional details to error message
        $errorMessage .= "<hr /><p><b>Debug Backtrace</b></p>" . $backtrace;
        $errorMessage .= "<hr /><p><b>Current In Scope Variables</b></p>" . $currentVars;

        echo $errorMessage;
        
        exit();
    }
    
}
