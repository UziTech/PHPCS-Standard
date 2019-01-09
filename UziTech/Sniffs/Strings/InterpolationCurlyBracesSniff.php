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
		return [T_DOUBLE_QUOTED_STRING];
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

		// If tabs are being converted to spaces by the tokeniser, the
		// original content should be used instead of the converted content.
		if (isset($tokens[$stackPtr]['orig_content']) === true) {
			$workingString = $tokens[$stackPtr]['orig_content'];
		} else {
			$workingString = $tokens[$stackPtr]['content'];
		}

		$lastStringToken = $stackPtr;

		$i = ($stackPtr + 1);
		if (isset($tokens[$i]) === true) {
			while ($i < $phpcsFile->numTokens
				&& $tokens[$i]['code'] === $tokens[$stackPtr]['code']
			) {
				if (isset($tokens[$i]['orig_content']) === true) {
					$workingString .= $tokens[$i]['orig_content'];
				} else {
					$workingString .= $tokens[$i]['content'];
				}

				$lastStringToken = $i;
				$i++;
			}
		}

		$skipTo = ($lastStringToken + 1);

		// Interpolated variables should be wrapped in curly braces.
		if ($tokens[$stackPtr]['code'] === T_DOUBLE_QUOTED_STRING) {
			$stringTokens = token_get_all('<?php '.$workingString);
			$error = false;
			foreach ($stringTokens as $key => $token) {
				if (is_array($token) === true && $token[0] === T_VARIABLE) {
					for ($i = $key - 1; $i >= 0; $i--) {
						$tokeni = isset($stringTokens[$i]) ? $stringTokens[$i] : null;
						$tokeni = is_array($tokeni) ? $tokeni : [null, $tokeni, 1];
						if ($tokeni[0] === T_OPEN_TAG || ($tokeni[0] === null && $tokeni[1] === "}")) {
							$error = "Interpolated variables should be wrapped in curly braces";
							break 2;
						} else if ($tokeni[0] === T_VARIABLE || $tokeni[0] === T_CURLY_OPEN) {
							break;
						}
					}
				}
			}

			if ($error) {
				$fix = $phpcsFile->addFixableError($error, $stackPtr, 'NoCurlyVarInterpolation');
				if ($fix === true) {
					$newString = "";
					while (strlen($workingString) > 0) {
						$matches = null;
						preg_match('/^(?:\\\\\\\\|\\\\\$|\{\$.+\}|\$[\W\d]|[^\$])+/', $workingString, $matches);
						if (isset($matches[0]) && strlen($matches[0]) > 0) {
							$newString .= $matches[0];
							$workingString = substr($workingString, strlen($matches[0]));
							continue;
						}
						preg_match('/^\$[^\W\d]\w*(?:\[[^\]]*\]|\([^)]*\)|->[^\W\d]\w*)*/', $workingString, $matches);
						if (isset($matches[0]) && strlen($matches[0]) > 0) {
							$newString .= "{{$matches[0]}}";
							$workingString = substr($workingString, strlen($matches[0]));
							continue;
						}

						// something went wrong
						return $skipTo;
					}


					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken($stackPtr, $newString);
					while ($lastStringToken !== $stackPtr) {
						$phpcsFile->fixer->replaceToken($lastStringToken, '');
						$lastStringToken--;
					}
					$phpcsFile->fixer->endChangeset();
				}
			}

			return $skipTo;
		}//end if
	}//end process()


}//end class
