<?php
/**
 * Generic_Sniffs_NamingConventions_UpperCaseConstantNameSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Generic_Sniffs_NamingConventions_UpperCaseConstantNameSniff.
 *
 * Ensures that constant names are all uppercase.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: 2.6.0
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class UziTech_Sniffs_NamingConventions_LowerCaseConstantSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_STRING);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens    = $phpcsFile->getTokens();
				//error_log(var_dump_r($tokens));
				//return;
        $constName = $tokens[$stackPtr]['content'];
				
				// Uppercase implies constant
				if($constName === strtoupper($constName)){
					return;
				}

        // If this token is in a heredoc, ignore it.
        if ($phpcsFile->hasCondition($stackPtr, T_START_HEREDOC) === true) {
            return;
        }

        // Special case for PHPUnit.
        if ($constName === 'PHPUnit_MAIN_METHOD') {
            return;
        }

				// TODO: Should probably blacklist surrounding tokens of variable rather that whitelist non-variables.
				
        // Check non-whitespace tokens around this token to see if it is interpreted as a constant
				$nextToken = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true, null, true);
        if ($nextToken !== false && in_array($tokens[$nextToken]['code'], [T_OPEN_PARENTHESIS, T_DOUBLE_COLON, T_VARIABLE, T_NS_SEPARATOR])) {
					// Is function call or start of static variable or variable type
					return;
				}
				
				$prevToken = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true, null, true);
        if ($prevToken === false || in_array($tokens[$prevToken]['code'], [T_FUNCTION, T_CLASS, T_DOUBLE_COLON, T_EXTENDS, T_IMPLEMENTS, T_OBJECT_OPERATOR, T_NAMESPACE, T_NS_SEPARATOR, T_NEW])) {
					// Is function/class name or namespace
					return;
				}

				// if it gets this far it is either lowercase constant or forgot the $ before variable
				
                $error = 'Did you forget a \'$\'; expected \'$%s\' found \'%s\'';
                $data  = array(
                          $constName,
                          $constName,
                         );
                $phpcsFile->addError($error, $stackPtr, 'ConstantNotUpperCase', $data);
								// Not fixable because we are not 100% sure that it is not a constant and it will change logic

    }//end process()


}//end class
