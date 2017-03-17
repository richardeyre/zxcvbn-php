<?php

namespace ZxcvbnPhp;

class Zxcvbn
{
    /**
     * @var
     */
    protected $scorer;

    /**
     * @var
     */
    protected $searcher;

    /**
     * @var
     */
    protected $matcher;

    /**
     * @var
     */
    protected $suggester;

    public function __construct()
    {
        $this->scorer = new \ZxcvbnPhp\Scorer();
        $this->searcher = new \ZxcvbnPhp\Searcher();
        $this->matcher = new \ZxcvbnPhp\Matcher();
        $this->suggester = new \ZxcvbnPhp\Suggester();
    }

    /**
     * Calculate password strength via non-overlapping minimum entropy patterns.
     *
     * @param string $password
     *   Password to measure.
     * @param array $userInputs
     *   Optional user inputs.
     *
     * @return array
     *   Strength result array with keys:
     *     password
     *     entropy
     *     match_sequence
     *     score
     */
    public function passwordStrength($password, array $userInputs = array())
    {
        $timeStart = microtime(true);
        if (strlen($password) === 0) {
            $timeStop = microtime(true) - $timeStart;
            return $this->result($password, 0, array(), 0, array('calc_time' => $timeStop));
        }

        // Get matches for $password.
        $matches = $this->matcher->getMatches($password, $userInputs);

        // Calcuate minimum entropy and get best match sequence.
        $entropy = $this->searcher->getMinimumEntropy($password, $matches);
        $bestMatches = $this->searcher->matchSequence;

        // Calculate score and get crack time.
        $score = $this->scorer->score($entropy);
        $metrics = $this->scorer->getMetrics();
        // Get suggestions
        $suggestions = $this->suggester->getFeedback($score, $bestMatches);

        $timeStop = microtime(true) - $timeStart;
        // Include metrics and calculation time.
        $params = array_merge($metrics, array('calc_time' => $timeStop));
        return $this->result($password, $entropy, $bestMatches, $score, $suggestions, $params);
    }

    /**
     * Result array.
     *
     * @param string $password
     * @param float $entropy
     * @param array $matches
     * @param int $score
     * @param array $suggestions
     * @param array $params
     * @return array
     */
    protected function result($password, $entropy, $matches, $score, $suggestions = array(), $params = array()) {
        $r = array(
            'password'       => $password,
            'entropy'        => $entropy,
            'match_sequence' => $matches,
            'score'          => $score,
            'suggestions'    => $suggestions,
        );
        return array_merge($params, $r);
    }

}