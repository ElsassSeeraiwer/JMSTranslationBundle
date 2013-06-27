<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JMS\TranslationBundle;

use Symfony\Component\Translation\Translator as OriginalTranslator;


/**
 * Translator.
 *
 * @api
 */
class Translator extends OriginalTranslator
{
    public function __construct() { 
        parent::__construct(); 
    } 
}
