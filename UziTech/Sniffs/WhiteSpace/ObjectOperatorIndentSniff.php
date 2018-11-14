<?php
/**
 * Checks that object operators are indented correctly.
 * Modified from PEAR\Sniffs\WhiteSpace\ObjectOperatorIndentSniff to add tab support
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace UziTech\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Config;

class ObjectOperatorIndentSniff implements Sniff {

	/**
	 * The number of spaces code should be indented.
	 *
	 * @var int
	 */
	public $indent = 4;

	// XXX: added by Tony
	/**
	 * Should tabs be used for indenting?
	 *
	 * If TRUE, fixes will be made using tabs instead of spaces.
	 * The size of each tab is important, so it should be specified
	 * using the --tab-width CLI argument.
	 *
	 * @var bool
	 */
	public $tabIndent = false;
	// XXX: added by Tony

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return int[]
	 */
	public function register() {
		return [T_OBJECT_OPERATOR];
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile All the tokens found in the document.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr) {

		// XXX: added by Tony
		if (isset($phpcsFile->config->tabWidth) === true && $phpcsFile->config->tabWidth > 0) {
			$this->indent = $phpcsFile->config->tabWidth;
		}
		// XXX: added by Tony

		$tokens = $phpcsFile->getTokens();

		// Make sure this is the first object operator in a chain of them.
		$varToken = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
		if ($varToken === false || $tokens[$varToken]['code'] !== T_VARIABLE) {
			return;
		}

		// Make sure this is a chained call.
		$next = $phpcsFile->findNext(
			T_OBJECT_OPERATOR,
			($stackPtr + 1),
			null,
			false,
			null,
			true
		);

		if ($next === false) {
			// Not a chained call.
			return;
		}

		// Determine correct indent.
		for ($i = ($varToken - 1); $i >= 0; $i--) {
			if ($tokens[$i]['line'] !== $tokens[$varToken]['line']) {
				$i++;
				break;
			}
		}

		$requiredIndent = 0;
		if ($i >= 0 && $tokens[$i]['code'] === T_WHITESPACE) {
			$requiredIndent = strlen($tokens[$i]['content']);
		}

		$requiredIndent += $this->indent;

		// Determine the scope of the original object operator.
		$origBrackets = null;
		if (isset($tokens[$stackPtr]['nested_parenthesis']) === true) {
			$origBrackets = $tokens[$stackPtr]['nested_parenthesis'];
		}

		$origConditions = null;
		if (isset($tokens[$stackPtr]['conditions']) === true) {
			$origConditions = $tokens[$stackPtr]['conditions'];
		}

		// Check indentation of each object operator in the chain.
		// If the first object operator is on a different line than
		// the variable, make sure we check its indentation too.
		if ($tokens[$stackPtr]['line'] > $tokens[$varToken]['line']) {
			$next = $stackPtr;
		}

		while ($next !== false) {
			// Make sure it is in the same scope, otherwise don't check indent.
			$brackets = null;
			if (isset($tokens[$next]['nested_parenthesis']) === true) {
				$brackets = $tokens[$next]['nested_parenthesis'];
			}

			$conditions = null;
			if (isset($tokens[$next]['conditions']) === true) {
				$conditions = $tokens[$next]['conditions'];
			}

			if ($origBrackets === $brackets && $origConditions === $conditions) {
				// Make sure it starts a line, otherwise dont check indent.
				$prev   = $phpcsFile->findPrevious(T_WHITESPACE, ($next - 1), $stackPtr, true);
				$indent = $tokens[($next - 1)];
				if ($tokens[$prev]['line'] !== $tokens[$next]['line']
					&& $indent['code'] === T_WHITESPACE
				) {
					if ($indent['line'] === $tokens[$next]['line']) {
						$foundIndent = strlen($indent['content']);
					} else {
						$foundIndent = 0;
					}

					if ($foundIndent !== $requiredIndent) {
						// XXX: added by Tony
						if ($this->tabIndent) {
							$error = 'Object operator not indented correctly; expected %s tabs but found %s';
							$data  = [
								$requiredIndent / $this->indent,
								$foundIndent / $this->indent,
							];

							$fix = $phpcsFile->addFixableError($error, $next, 'IncorrectIndent', $data);
							if ($fix === true) {
								$tabs = str_repeat("\t", $requiredIndent / $this->indent);
								if ($foundIndent === 0) {
									$phpcsFile->fixer->addContentBefore($next, $tabs);
								} else {
									$phpcsFile->fixer->replaceToken(($next - 1), $tabs);
								}
							}
						} else {
						// XXX: added by Tony
							$error = 'Object operator not indented correctly; expected %s spaces but found %s';
							$data  = [
								$requiredIndent,
								$foundIndent,
							];

							$fix = $phpcsFile->addFixableError($error, $next, 'IncorrectIndent', $data);
							if ($fix === true) {
								$spaces = str_repeat(' ', $requiredIndent);
								if ($foundIndent === 0) {
									$phpcsFile->fixer->addContentBefore($next, $spaces);
								} else {
									$phpcsFile->fixer->replaceToken(($next - 1), $spaces);
								}
							}
						}
					// XXX: added by Tony
					}
					// XXX: added by Tony
				}//end if

				// It cant be the last thing on the line either.
				$content = $phpcsFile->findNext(T_WHITESPACE, ($next + 1), null, true);
				if ($tokens[$content]['line'] !== $tokens[$next]['line']) {
					$error = 'Object operator must be at the start of the line, not the end';
					$fix   = $phpcsFile->addFixableError($error, $next, 'StartOfLine');
					if ($fix === true) {
						$phpcsFile->fixer->beginChangeset();
						for ($x = ($next + 1); $x < $content; $x++) {
							$phpcsFile->fixer->replaceToken($x, '');
						}

						$phpcsFile->fixer->addNewlineBefore($next);
						$phpcsFile->fixer->endChangeset();
					}
				}
			}//end if

			$next = $phpcsFile->findNext(
				T_OBJECT_OPERATOR,
				($next + 1),
				null,
				false,
				null,
				true
			);
		}//end while
	}//end process()


}//end class
