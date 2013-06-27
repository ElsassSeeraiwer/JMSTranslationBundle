<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\TranslationBundle\Twig;

/**
 * Provides some extensions for specifying translation metadata.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;

class TranslationExtension extends \Twig_Extension
{
    private $configFactory;
    private $translator;
    private $request;
    private $debug;

    public function __construct(TranslatorInterface $translator, $debug = false, $configFactory)
    {
        $this->translator = $translator;
        $this->debug = $debug;
        $this->configFactory = $configFactory;
    }

    public function onKernelRequest(GetResponseEvent $event) {
        if ($event->getRequestType() === HttpKernel::MASTER_REQUEST) {
            $this->request = $event->getRequest();
        }
    }

    public function getNodeVisitors()
    {
        $visitors = array(
            new NormalizingNodeVisitor(),
            new RemovingNodeVisitor(),
        );

        if ($this->debug) {
            $visitors[] = new DefaultApplyingNodeVisitor();
        }

        return $visitors;
    }

    public function getFilters()
    {
        return array(
            'desc' => new \Twig_Filter_Method($this, 'desc'),
            'meaning' => new \Twig_Filter_Method($this, 'meaning'),
            'transEdit' => new \Twig_Filter_Method($this, 'editTrans', array('is_safe' => array('html'))),
            'transchoiceEdit' => new \Twig_Filter_Method($this, 'editTransChoise', array('is_safe' => array('html'))),
            'trans' => new \Twig_Filter_Method($this, 'trans'),
            'transchoice' => new \Twig_Filter_Method($this, 'transchoice'),
        );
    }

    public function getFunctions()
    {
        return array(
            'transEditScript' => new \Twig_Function_Method($this, 'editTransScript', array('needs_environment' => true, 'is_safe' => array('all'))),
        );
    }

    public function trans($message, array $arguments = array(), $domain = null, $locale = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        return $this->translator->trans($message, $arguments, $domain, $locale);
    }

    public function transchoice($message, $count, array $arguments = array(), $domain = null, $locale = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        return $this->translator->transChoice($message, $count, array_merge(array('%count%' => $count), $arguments), $domain, $locale);
    }

    public function editTrans($message, array $arguments = array(), $domain = null, $locale = null, $config = null)
    {
        $configs = $this->configFactory->getNames();
        $config = $config ?: reset($configs);

        if (null === $domain) {
            $domain = 'messages';
        }

        if (null === $locale) {
            $locale = $this->request->getLocale();
        }

        $content = '<span data-config="'.$config.'" data-id="'.$message.'" data-domain="'.$domain.'" data-locale="'.$locale.'" class="transEdit">'.$this->trans($message, $arguments, $domain, $locale).'</span>';
        return $content;
    }

    public function editTransChoice($message, $count, array $arguments = array(), $domain = null, $locale = null, $config = null)
    {
        $configs = $this->configFactory->getNames();
        $config = $config ?: reset($configs);

        if (null === $domain) {
            $domain = 'messages';
        }

        if (null === $locale) {
            $locale = $this->request->getLocale();
        }

        $content = '<span data-config="'.$config.'" data-id="'.$message.'" data-domain="'.$domain.'" data-locale="'.$locale.'" class="transChoiceEdit">'.$this->transchoice($message, $count, $arguments, $domain, $locale).'</span>';
        return $content;
    }

    public function editTransScript($env, $authorized_role = 'ROLE_SUPER_ADMIN', $jquery = true)
    {
        $content = $env->render('JMSTranslationBundle:TransEdit:transEditScript.html.twig', array(
            'authorized_role'   => $authorized_role,
            'jQuery'            => $jquery
        ));

        return $content;
    }

    public function transchoiceWithDefault($message, $defaultMessage, $count, array $arguments = array(), $domain = null, $locale = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        try {
            return $this->translator->transChoice($message, $count, array_merge(array('%count%' => $count), $arguments), $domain, $locale);
        } catch (\InvalidArgumentException $unableToChooseTranslationEx) {
            return $this->translator->transChoice($defaultMessage, $count, array_merge(array('%count%' => $count), $arguments), $domain, $locale);
        }
    }

    public function desc($v)
    {
        return $v;
    }

    public function meaning($v)
    {
        return $v;
    }

    public function getName()
    {
        return 'itrf_website';
    }
}