<?php
/**
 * Version1.php
 * @author    Daniel Mason <daniel@ayeayeapi.com>
 * @copyright (c) 2016 Daniel Mason <daniel@ayeayeapi.com>
 * @license   MIT
 * @see       https://github.com/AyeAyeApi/behat-feature-context
 */

namespace AyeAye\Behat\Mock\Api;

use AyeAye\Api\Controller;
use AyeAye\Api\Exception as AyeAyeException;

class Version1 extends Controller
{

    /**
     * Version1 constructor.
     * Removes the coffee endpoint from the index.
     */
    public function __construct()
    {
        $this->hideMethod('getCoffeeEndpoint');
    }

    /**
     * Self referenced controller
     * @return $this
     * @throws AyeAyeException
     */
    public function version1Controller()
    {
        $this->hideMethod('version1Controller');
        return $this;
    }

    /**
     * I am a teapot
     * @throws AyeAyeException
     */
    public function getCoffeeEndpoint()
    {
        throw new AyeAyeException(418);
    }

}
