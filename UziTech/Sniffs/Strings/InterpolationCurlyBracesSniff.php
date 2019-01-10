<?php
/**
 * Ensures that variables don't forget '$'.
 *
 * @author    Tony Brix
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace UziTech\Sniffs\Strings;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class InterpolationCurlyBracesSniff implements Sniff {


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [T_DOUBLE_QUOTED_STRING, T_HEREDOC];
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$isHeredoc = $tokens[$stackPtr]['code'] === T_HEREDOC;

		$workingString = "";
		$nextToken = $stackPtr;
		do {
			$workingString .= isset($tokens[$nextToken]['orig_content']) ? $tokens[$nextToken]['orig_content'] : $tokens[$nextToken]['content'];
			// $workingString .= $tokens[$nextToken]['orig_content'] ?? $tokens[$nextToken]['content'];
			$nextToken++;
		} while ($nextToken < $phpcsFile->numTokens && $tokens[$nextToken]['code'] === $tokens[$stackPtr]['code']);
		$skipTo = $nextToken;

		$errorVars = [];
		$newString = "";
		while (strlen($workingString) > 0) {
			$matches = null;
			$isNotVar = preg_match('/^(?:\\\\.|\\{\\$[^{]+?\\}|\\$[\\W\\d]|[^\\$])+/', $workingString, $matches);
			$isVar = !$isNotVar && preg_match('/^\\$[^\\W\\d]\\w*(?:\\[[^\\]]*\\]|\\([^)]*\\)|->[^\\W\\d]\\w*)*/', $workingString, $matches);
			if (isset($matches[0]) && strlen($matches[0]) > 0) {
				if ($isVar) {
					$newString .= "{{$matches[0]}}";
					$errorVars[] = $matches[0];
				} else {
					$newString .= $matches[0];
				}
				$workingString = substr($workingString, strlen($matches[0]));
			} else {
				// something went wrong
				return $skipTo;
			}
		}

		if (count($errorVars) > 0) {
			$fix = $phpcsFile->addFixableError("The following interpolated variables should be wrapped in curly braces: '" . implode("', '", $errorVars) . "'", $stackPtr, 'NoCurlyVarInterpolation');
			if ($fix) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->replaceToken($stackPtr, $newString);

				// <-- is the "goes to" operator. See https://stackoverflow.com/a/1642035/806777
				while ($stackPtr <-- $nextToken) {
					$phpcsFile->fixer->replaceToken($nextToken, '');
				}
				$phpcsFile->fixer->endChangeset();
			}
		}

		return $skipTo;
	}//end process()


}//end class
