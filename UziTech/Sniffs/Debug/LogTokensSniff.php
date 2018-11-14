<?php
/*
 * Log all tokens to error_log
 */


namespace UziTech\Sniffs\Debug;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class LogTokensSniff implements Sniff {

	public $debug = false;


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [T_OPEN_TAG];
	}//end register()


	/**
	 * Processes this sniff, when one of its tokens is encountered.
	 *
	 * @param File $phpcsFile The current file being checked.
	 * @param int                  $stackPtr  The position of the current token in
	 *                                        the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr) {
		if ($this->debug) {
			$tokens = $phpcsFile->getTokens();
			ob_start();
			var_dump($tokens);
			$varDump = ob_get_clean();
			error_log($varDump);
		}
	}//end process()

}//end class
