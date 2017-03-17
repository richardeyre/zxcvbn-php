<?php

namespace ZxcvbnPhp;

use ZxcvbnPhp\Matchers\Match;

class Suggester
{
    /**
     * @param int $score
     * @param array $sequence
     * @return array
     */
    public function getFeedback($score, $sequence)
    {
        if (sizeof($sequence) === 0) {
            return array(
                'warning' => '',
                'suggestions' => array(
                    'Use a few words, avoid common phrases',
                    'No need for symbols, digits, or uppercase letters'
                ),
            );
        }
        $longest = $sequence[0];
        for ($i = 1; $i < sizeof($sequence); $i++) {
            if (sizeof($sequence[$i]->token) > sizeof($longest->token)) {
                $longest = $sequence[$i];
            }
        }
        $feedback = $this->getMatchFeedback($longest, sizeof($sequence) === 1);
    }

    /**
     * @param Match $match
     * @param bool $isSoleMatch
     * @return array
     */
    private function getMatchFeedback($match, $isSoleMatch)
    {
        $warning = '';
        $suggestions = array();

        switch ($match->pattern) {
            case 'dictionary';
                if ($match->dictionaryName === 'passwords') {
                    if ($isSoleMatch and !isset($match->l33t)) {
                        if ($match->rank <= 10) {
                            $warning = 'This is a top-10 common password';
                        } elseif ($match->rank <= 100) {
                            $warning = 'This is a top-100 common password';
                        } else {
                            $warning = 'This is a very common password';
                        }
                    }
                } elseif ($match->dictionaryName === 'english') {
                    if ($isSoleMatch) {
                        $warning = 'A word by itself is easy to guess';
                    }
                } elseif (in_array($match->dictionaryName, array('surnames', 'male_names', 'female_names'))) {
                    if ($isSoleMatch) {
                        $warning = 'Names and surnames by themselves are easy to guess';
                    } else {
                        $warning = 'Common names and surnames are easy to guess';
                    }
                }
                $word = $match->token;
                if (preg_match('/^[A-Z][^A-Z]+$/', $word)) {
                    $suggestions[] = 'Capitalization doesn\'t help very much';
                } elseif (preg_match('/^[^a-z]+$/', $word) && strtolower($word) != $word) {
                    $suggestions[] = 'All-uppercase is almost as easy to guess as all-lowercase';
                }
                if (isset($match->l33t) && $match->l33t) {
                    $suggestions[] = 'Predictable substitutions like \'@\' instead of \'a\' don\'t help very much';
                }
                break;
            case 'spatial':
                $warning = $match->turns === 1
                    ? 'Straight rows of keys are easy to guess'
                    : 'Short keyboard patterns are easy to guess';
                $suggestions[] = 'Use a longer keyboard pattern with more turns';
                break;
            case 'repeat':
                $warning = sizeof($match->repeatedChar) === 1
                    ? 'Repeats like "aaa" are easy to guess'
                    : 'Repeats like "abcabcabc" are only slightly harder to guess than "abc"';
                $suggestions[] = 'Avoid repeated words and characters';
                break;
            case 'sequence':
                $warning = "Sequences like abc or 6543 are easy to guess";
                $suggestions[] = 'Avoid sequences';
                break;
            case 'date':
                $warning = 'Dates are often easy to guess';
                $suggestions[] = 'Avoid dates and years that are associated with you';
                break;
        }

        return array(
            'warning' => $warning,
            'suggestions' => $suggestions,
        );
    }
}
