<?php

class EcomDev_Sphinx_Model_Source_Morphology
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{
    const STEM_DANNISH = 'libstemmer_dan';
    const STEM_DUTCH = 'libstemmer_dut';
    const STEM_ENGLISH = 'libstemmer_eng';
    const STEM_FINNISH = 'libstemmer_fin';
    const STEM_FRENCH = 'libstemmer_fra';
    const STEM_GERMAN = 'libstemmer_ger';
    const STEM_HUNGARIAN = 'libstemmer_hun';
    const STEM_ITALIAN = 'libstemmer_ita';
    const STEM_NORWEGIAN = 'libstemmer_nor';
    const STEM_PORTUGUESE = 'libstemmer_por';
    const STEM_ROMANIAN = 'libstemmer_rum';
    const STEM_RUSSIAN = 'libstemmer_rus';
    const STEM_SPANISH = 'libstemmer_spa';
    const STEM_SWEDISH = 'libstemmer_swe';
    const STEM_TURKISH = 'libstemmer_tur';

    protected function _initOptions()
    {
        $this->_options = array(
            self::STEM_DANNISH => $this->__('Danish'),
            self::STEM_DUTCH => $this->__('Dutch'),
            self::STEM_ENGLISH => $this->__('English'),
            self::STEM_FINNISH => $this->__('Finnish'),
            self::STEM_FRENCH => $this->__('French'),
            self::STEM_GERMAN => $this->__('German'),
            self::STEM_HUNGARIAN => $this->__('Hungarian'),
            self::STEM_ITALIAN => $this->__('Italian'),
            self::STEM_NORWEGIAN => $this->__('Norwegian'),
            self::STEM_PORTUGUESE => $this->__('Portuguese'),
            self::STEM_ROMANIAN => $this->__('Romanian'),
            self::STEM_RUSSIAN => $this->__('Russian'),
            self::STEM_SPANISH => $this->__('Spanish'),
            self::STEM_SWEDISH => $this->__('Swedish'),
            self::STEM_TURKISH => $this->__('Turkish')
        );
    }

    /**
     * Translate for string
     *
     * @param string $text
     * @return string
     */
    public function __($text)
    {
        return Mage::helper('ecomdev_sphinx')->__($text);
    }
}
