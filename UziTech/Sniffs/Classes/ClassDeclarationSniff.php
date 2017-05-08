<?php
/**
 * Checks the declaration of the class is correct.
 *
 * @author    Tony Brix
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace UziTech\Sniffs\Classes;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class ClassDeclarationSniff implements Sniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                T_CLASS,
                T_INTERFACE,
                T_TRAIT,
               );

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param integer                     $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens    = $phpcsFile->getTokens();
        $errorData = array(strtolower($tokens[$stackPtr]['content']));

        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            $error = 'Possible parse error: %s missing opening or closing brace';
            $phpcsFile->addWarning($error, $stackPtr, 'MissingBrace', $errorData);
            return;
        }

        $curlyBrace  = $tokens[$stackPtr]['scope_opener'];
        $lastContent = $phpcsFile->findPrevious(T_WHITESPACE, ($curlyBrace - 1), $stackPtr, true);
        $classLine   = $tokens[$lastContent]['line'];
        $braceLine   = $tokens[$curlyBrace]['line'];
        if ($braceLine !== $classLine) {
            //$phpcsFile->recordMetric($stackPtr, 'Class opening brace placement', 'same line');
            $error = 'Opening brace of a %s must be on the same line as the definition';
            $fix   = $phpcsFile->addFixableError($error, $curlyBrace, 'OpenBraceSameLine', $errorData);
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                $whiteSpace = $curlyBrace - 1;
                while($tokens[$whiteSpace]['code'] === T_WHITESPACE){
                    $phpcsFile->fixer->replaceToken($whiteSpace, '');
                    $whiteSpace--;
                }
                $phpcsFile->fixer->addContentBefore($curlyBrace, ' ');

                $phpcsFile->fixer->endChangeset();
            }

            return;
        } else if($tokens[($curlyBrace - 1)]['content'] !== ' ') {
          $spaces = 0;
          if($tokens[($curlyBrace - 1)]['code'] === T_WHITESPACE){
            $spaces = strlen($tokens[($curlyBrace - 1)]['content']);
          }

            $error = 'Expected 1 spaces before opening brace; %s found';
                $data  = array(
                          $spaces,
                         );

                $fix = $phpcsFile->addFixableError($error, $curlyBrace, 'SpaceBeforeBrace', $data);
                if ($fix === true) {
                    if ($spaces === 0) {
                        $phpcsFile->fixer->addContentBefore($curlyBrace, ' ');
                    } else {
                        $phpcsFile->fixer->replaceToken(($curlyBrace - 1), ' ');
                    }
                }
                return;
        }//end if
    }//end process()
}//end class
