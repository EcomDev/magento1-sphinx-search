<?php

use \Foolz\SphinxQL\SphinxQL;

class EcomDev_Sphinx_Model_Sphinx_Query_Builder
    extends SphinxQL 
{
    public function __construct(\Foolz\SphinxQL\Drivers\ConnectionInterface $connection, $static = false)
    {
        parent::__construct($connection, $static);
        $this->escape_full_chars['*'] = '\\*';
        $this->escape_full_chars['.'] = '\\.';
    }


    /**
     * Makes possible to add more select fields 
     * after initial fields was added
     * 
     * @return $this
     */
    public function select($columns = null)
    {
        $select = func_get_args();
        
        if ($this->type !== 'select' || empty($select)) {
            parent::select($select);
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

        if (!empty($this->match)) {
            $query .= 'WHERE MATCH(';

            $matched = array();

            foreach ($this->match as $match) {
                $pre = '';
                if (is_callable($match['column'])) {
                    $sub = new Match($this);
                    call_user_func($match['column'], $sub);
                    $pre .= $sub->compile()->getCompiled();
                } elseif ($match['column'] instanceof Match) {
                    $pre .= $match['column']->compile()->getCompiled();
                } elseif (empty($match['column'])) {
                    $pre .= '';
                } elseif (is_array($match['column'])) {
                    $pre .= '@('.implode(',', $match['column']).') ';
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
            $query .= $this->connection->quote($matched) . ') ';
        }

        return $query;
    }

    /**
     * Escapes the query for the MATCH() function
     *
     * @param string $string The string to escape for the MATCH
     *
     * @return string The escaped string
     */
    public function escapeMatch($string)
    {
        $match = implode('', array_keys($this->escape_full_chars));
        return addcslashes($string, $match);
    }


}
