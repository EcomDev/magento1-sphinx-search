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
    const RTL_CHINESE = ' stem_en, rlp_chinese_batched';
    const DEFAULT_ENGLISH = 'stem_en';
    const DEFAULT_RUSSIAN = 'stem_ru';
    const DEFAULT_CZECH = 'stem_cz';

    protected function _initOptions()
    {
        $this->_options = array(
            self::DEFAULT_ENGLISH => $this->__('English (default)'),
            self::DEFAULT_RUSSIAN => $this->__('Russian (default)'),
            self::DEFAULT_CZECH => $this->__('Czech (default)'),
            self::STEM_DANNISH => $this->__('Danish (snowball)'),
            self::STEM_DUTCH => $this->__('Dutch (snowball)'),
            self::STEM_ENGLISH => $this->__('English  (snowball)'),
            self::STEM_FINNISH => $this->__('Finnish (snowball)'),
            self::STEM_FRENCH => $this->__('French (snowball)'),
            self::STEM_GERMAN => $this->__('German (snowball)'),
            self::STEM_HUNGARIAN => $this->__('Hungarian (snowball)'),
            self::STEM_ITALIAN => $this->__('Italian (snowball)'),
            self::STEM_NORWEGIAN => $this->__('Norwegian (snowball)'),
            self::STEM_PORTUGUESE => $this->__('Portuguese (snowball)'),
            self::STEM_ROMANIAN => $this->__('Romanian (snowball)'),
            self::STEM_RUSSIAN => $this->__('Russian (snowball)'),
            self::STEM_SPANISH => $this->__('Spanish (snowball)'),
            self::STEM_SWEDISH => $this->__('Swedish (snowball)'),
            self::STEM_TURKISH => $this->__('Turkish (snowball)')
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
