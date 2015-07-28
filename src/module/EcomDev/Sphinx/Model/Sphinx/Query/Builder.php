<?php

use \Foolz\SphinxQL\SphinxQL;

class EcomDev_Sphinx_Model_Sphinx_Query_Builder
    extends SphinxQL 
{
    /**
     * Makes possible to add more select fields 
     * after initial fields was added
     * 
     * @return $this
     */
    public function select()
    {
        $select = func_get_args();
        
        if ($this->type !== 'select' || empty($select)) {
            parent::select();
            $this->select = $select;
        } else {
            foreach ($select as $item) {
                $this->select[] = $item;
            }
        }
        
        return $this;
    }

    /**
     * Quotes identifier
     * 
     * @param $identifier
     * @return \Foolz\SphinxQL\Expression|string
     */
    public function quoteIdentifier($identifier)
    {
        return $this->getConnection()->quoteIdentifier($identifier);
    }

    /**
     * Makes possible to wrap arguments into sprintf
     * 
     * @return \Foolz\SphinxQL\Expression
     */
    public function exprFormat()
    {
        $args = func_get_args();
        return $this->expr(call_user_func_array('sprintf', $args));
    }

    /**
     * Compiles the MATCH part of the queries
     * Used by: SELECT, DELETE, UPDATE
     *
     * @return string The compiled MATCH
     */
    public function compileMatch()
    {
        $query = '';

        if ( ! empty($this->match)) {
            $query .= 'WHERE MATCH(';

            $matched = array();

            foreach ($this->match as $match) {
                $pre = '';
                if (empty($match['column'])) {
                    $pre .= '';
                } elseif (is_array($match['column'])) {
                    $pre .= '@('.implode(',',$match['column']).') ';
                } else {
                    $pre .= '@'.$match['column'].' ';
                }

                if ($match['half']) {
                    $pre .= $this->halfEscapeMatch($match['value']);
                } else {
                    $pre .= $this->escapeMatch($match['value']);
                }

                $matched[] = $pre;
            }

            if (count($matched) === 1) {
                $matched = current($matched);
            } else {
                $matched = '(' . implode(') (', $matched ) .')';
            }
            $query .= $this->getConnection()->escape(trim($matched)).') ';
        }
        
        return $query;
    }
}
