<?php

use iEducar\Legacy\Model;

require_once 'include/urbano/geral.inc.php';

class clsUrbanoCepLogradouroBairro extends Model
{
    public $idlog;
    public $cep;
    public $idbai;
    public $idpes_rev;
    public $data_rev;
    public $origem_gravacao;
    public $idpes_cad;
    public $data_cad;
    public $operacao;

    public function __construct($idlog = null, $cep = null, $idbai = null, $idpes_rev = null, $data_rev = null, $origem_gravacao = null, $idpes_cad = null, $data_cad = null, $operacao = null)
    {
        $db = new clsBanco();
        $this->_schema = 'urbano.';
        $this->_tabela = "{$this->_schema}cep_logradouro_bairro";
        $this->_campos_lista = $this->_todos_campos = 'clb.idlog, clb.cep, clb.idbai, clb.idpes_rev, clb.data_rev, clb.origem_gravacao, clb.idpes_cad, clb.data_cad, clb.operacao';

        if (is_numeric($idpes_rev)) {
                    $this->idpes_rev = $idpes_rev;
        }
        if (is_numeric($idpes_cad)) {
                    $this->idpes_cad = $idpes_cad;
        }
        if (is_numeric($cep) && is_numeric($idlog)) {
                    $this->cep = $cep;
                    $this->idlog = $idlog;
        }
        if (is_numeric($idbai)) {
                    $this->idbai = $idbai;
        }
        if (is_string($data_rev)) {
            $this->data_rev = $data_rev;
        }
        if (is_string($origem_gravacao)) {
            $this->origem_gravacao = $origem_gravacao;
        }
        if (is_string($data_cad)) {
            $this->data_cad = $data_cad;
        }
        if (is_string($operacao)) {
            $this->operacao = $operacao;
        }
    }

    /**
     * Cria um novo registro
     *
     * @return bool
     */
    public function cadastra()
    {
        if (is_numeric($this->idlog) && is_numeric($this->cep) && is_numeric($this->idbai) && is_string($this->origem_gravacao) && is_string($this->operacao)) {
            $db = new clsBanco();
            $campos = '';
            $valores = '';
            $gruda = '';
            if (is_numeric($this->idlog)) {
                $campos .= "{$gruda}idlog";
                $valores .= "{$gruda}'{$this->idlog}'";
                $gruda = ', ';
            }
            if (is_numeric($this->cep)) {
                $campos .= "{$gruda}cep";
                $valores .= "{$gruda}'{$this->cep}'";
                $gruda = ', ';
            }
            if (is_numeric($this->idbai)) {
                $campos .= "{$gruda}idbai";
                $valores .= "{$gruda}'{$this->idbai}'";
                $gruda = ', ';
            }
            if (is_numeric($this->idpes_rev)) {
                $campos .= "{$gruda}idpes_rev";
                $valores .= "{$gruda}'{$this->idpes_rev}'";
                $gruda = ', ';
            }
            if (is_string($this->data_rev)) {
                $campos .= "{$gruda}data_rev";
                $valores .= "{$gruda}'{$this->data_rev}'";
                $gruda = ', ';
            }
            if (is_string($this->origem_gravacao)) {
                $campos .= "{$gruda}origem_gravacao";
                $valores .= "{$gruda}'{$this->origem_gravacao}'";
                $gruda = ', ';
            }
            if (is_numeric($this->idpes_cad)) {
                $campos .= "{$gruda}idpes_cad";
                $valores .= "{$gruda}'{$this->idpes_cad}'";
                $gruda = ', ';
            }
            $campos .= "{$gruda}data_cad";
            $valores .= "{$gruda}NOW()";
            $gruda = ', ';
            if (is_string($this->operacao)) {
                $campos .= "{$gruda}operacao";
                $valores .= "{$gruda}'{$this->operacao}'";
                $gruda = ', ';
            }
            $db->Consulta("INSERT INTO {$this->_tabela} ( $campos ) VALUES( $valores )");

            return true;
        }

        return false;
    }

    /**
     * Edita os dados de um registro
     *
     * @return bool
     */
    public function edita()
    {
        if (is_numeric($this->idbai) && is_numeric($this->idlog) && is_numeric($this->cep)) {
            $this->cep == '' ? 0 : $this->cep;
            $db = new clsBanco();
            $set = '';
            if (is_numeric($this->idpes_rev)) {
                $set .= "{$gruda}idpes_rev = '{$this->idpes_rev}'";
                $gruda = ', ';
            }
            if (is_string($this->data_rev)) {
                $set .= "{$gruda}data_rev = '{$this->data_rev}'";
                $gruda = ', ';
            }
            if (is_string($this->origem_gravacao)) {
                $set .= "{$gruda}origem_gravacao = '{$this->origem_gravacao}'";
                $gruda = ', ';
            }
            if (is_numeric($this->idpes_cad)) {
                $set .= "{$gruda}idpes_cad = '{$this->idpes_cad}'";
                $gruda = ', ';
            }
            if (is_string($this->data_cad)) {
                $set .= "{$gruda}data_cad = '{$this->data_cad}'";
                $gruda = ', ';
            }
            if (is_string($this->operacao)) {
                $set .= "{$gruda}operacao = '{$this->operacao}'";
                $gruda = ', ';
            }
            if ($set) {
                $db->Consulta("UPDATE {$this->_tabela} SET $set WHERE idbai = '{$this->idbai}' AND idlog = '{$this->idlog}' AND cep = '{$this->cep}'");

                return true;
            }
        }

        return false;
    }

    public function editaCepBairro($cep, $idBairro)
    {
        $cep == '' ? $cep = 0 : $cep = $cep;
        $db = new clsBanco();
        $db->Consulta("UPDATE {$this->_tabela} SET idbai = '{$this->idbai}', cep = '{$this->cep}' WHERE idbai = $idBairro AND idlog = '{$this->idlog}' AND cep = '$cep'");

        return true;
    }

    /**
     * Retorna uma lista filtrados de acordo com os parametros
     *
     * @param integer int_idpes_rev
     * @param string date_data_rev_ini
     * @param string date_data_rev_fim
     * @param string str_origem_gravacao
     * @param integer int_idpes_cad
     * @param string date_data_cad_ini
     * @param string date_data_cad_fim
     * @param string str_operacao
     *
     * @return array
     */
    public function lista($int_idpes_rev = null, $date_data_rev_ini = null, $date_data_rev_fim = null, $str_origem_gravacao = null, $int_idpes_cad = null, $date_data_cad_ini = null, $date_data_cad_fim = null, $str_operacao = null, $int_idsis_rev = null, $int_idsis_cad = null, $int_idpais = null, $str_sigla_uf = null, $int_idmun = null, $int_idlog = null, $int_cep = null, $int_idbai = null)
    {
        $select = ', l.nome AS nm_logradouro, m.nome AS nm_municipio, m.sigla_uf, u.nome AS nm_estado, u.idpais, p.nome AS nm_pais ';
        $from = 'clb, public.logradouro l, public.municipio m, public.uf u, public.pais p ';

        $sql = "SELECT {$this->_campos_lista}{$select} FROM {$this->_tabela} {$from}";
        $whereAnd = ' AND ';
        $filtros = ' WHERE clb.idlog = l.idlog AND l.idmun = m.idmun AND m.sigla_uf = u.sigla_uf AND u.idpais = p.idpais ';
        if (is_numeric($int_idlog)) {
            $filtros .= "{$whereAnd} clb.idlog = '{$int_idlog}'";
            $whereAnd = ' AND ';
        }
        if (is_numeric($int_cep)) {
            $filtros .= "{$whereAnd} clb.cep = '{$int_cep}'";
            $whereAnd = ' AND ';
        }
        if (is_numeric($int_idbai)) {
            $filtros .= "{$whereAnd} clb.idbai = '{$int_idbai}'";
            $whereAnd = ' AND ';
        }
        if (is_numeric($int_idpes_rev)) {
            $filtros .= "{$whereAnd} clb.idpes_rev = '{$int_idpes_rev}'";
            $whereAnd = ' AND ';
        }
        if (is_string($date_data_rev_ini)) {
            $filtros .= "{$whereAnd} clb.data_rev >= '{$date_data_rev_ini}'";
            $whereAnd = ' AND ';
        }
        if (is_string($date_data_rev_fim)) {
            $filtros .= "{$whereAnd} clb.data_rev <= '{$date_data_rev_fim}'";
            $whereAnd = ' AND ';
        }
        if (is_string($str_origem_gravacao)) {
            $filtros .= "{$whereAnd} clb.origem_gravacao LIKE '%{$str_origem_gravacao}%'";
            $whereAnd = ' AND ';
        }
        if (is_numeric($int_idpes_cad)) {
            $filtros .= "{$whereAnd} clb.idpes_cad = '{$int_idpes_cad}'";
            $whereAnd = ' AND ';
        }
        if (is_string($date_data_cad_ini)) {
            $filtros .= "{$whereAnd} clb.data_cad >= '{$date_data_cad_ini}'";
            $whereAnd = ' AND ';
        }
        if (is_string($date_data_cad_fim)) {
            $filtros .= "{$whereAnd} clb.data_cad <= '{$date_data_cad_fim}'";
            $whereAnd = ' AND ';
        }
        if (is_string($str_operacao)) {
            $filtros .= "{$whereAnd} clb.operacao LIKE '%{$str_operacao}%'";
            $whereAnd = ' AND ';
        }
        if (is_numeric($int_idpais)) {
            $filtros .= "{$whereAnd} p.idpais = '{$int_idpais}'";
            $whereAnd = ' AND ';
        }
        if (is_string($str_sigla_uf)) {
            $filtros .= "{$whereAnd} u.sigla_uf = '{$str_sigla_uf}'";
            $whereAnd = ' AND ';
        }
        if (is_numeric($int_idmun)) {
            $filtros .= "{$whereAnd} m.idmun = '{$int_idmun}'";
            $whereAnd = ' AND ';
        }
        $db = new clsBanco();
        $countCampos = count(explode(',', $this->_campos_lista));
        $resultado = [];
        $sql .= $filtros . $this->getOrderby() . $this->getLimite();
        //echo "<pre>"; print_r($sql);die;
        $this->_total = $db->CampoUnico("SELECT COUNT(0) FROM {$this->_tabela} {$from}{$filtros}");
        $db->Consulta($sql);
        if ($countCampos > 1) {
            while ($db->ProximoRegistro()) {
                $tupla = $db->Tupla();
                $tupla['_total'] = $this->_total;
                $resultado[] = $tupla;
            }
        } else {
            while ($db->ProximoRegistro()) {
                $tupla = $db->Tupla();
                $resultado[] = $tupla[$this->_campos_lista];
            }
        }
        if (count($resultado)) {
            return $resultado;
        }

        return false;
    }

    public function listabai($int_idmun = null, $int_idlog = null, $int_cep = null, $int_idbai = null)
    {
        $select = 'l.nome AS nm_logradouro, m.nome AS nm_municipio, m.sigla_uf, u.nome AS nm_estado, u.idpais, p.nome AS nm_pais ';
        $from = 'public.logradouro l, public.municipio m, public.uf u, public.pais p ';

        $sql = "SELECT {$this->_campos_lista}{$select} FROM {$this->_tabela} {$from}";
        $whereAnd = ' AND ';
        $filtros = ' WHERE l.idmun = m.idmun AND m.sigla_uf = u.sigla_uf AND u.idpais = p.idpais  ';
        if (is_numeric($int_idlog)) {
            $filtros .= "{$whereAnd} clb.idlog = '{$int_idlog}'";
            $whereAnd = ' AND ';
        }
        if (is_numeric($int_cep)) {
            $filtros .= "{$whereAnd} clb.cep = '{$int_cep}'";
            $whereAnd = ' AND ';
        }
        if (is_numeric($int_idbai)) {
            $filtros .= "{$whereAnd} clb.idbai = '{$int_idbai}'";
            $whereAnd = ' AND ';
        }

        if (is_numeric($int_idmun)) {
            $filtros .= "{$whereAnd} m.idmun = '{$int_idmun}'";
            $whereAnd = ' AND ';
        }
        $db = new clsBanco();
        $countCampos = count(explode(',', $this->_campos_lista));
        $resultado = [];
        $sql .= $filtros . $this->getOrderby() . $this->getLimite();
        $this->_total = $db->CampoUnico("SELECT COUNT(0) FROM {$this->_tabela} {$from}{$filtros}");
        $db->Consulta($sql);
        if ($countCampos > 1) {
            while ($db->ProximoRegistro()) {
                $tupla = $db->Tupla();
                $tupla['_total'] = $this->_total;
                $resultado[] = $tupla;
            }
        } else {
            while ($db->ProximoRegistro()) {
                $tupla = $db->Tupla();
                $resultado[] = $tupla[$this->_campos_lista];
            }
        }
        if (count($resultado)) {
            return $resultado;
        }

        return false;
    }

    /**
     * Retorna um array com os dados de um registro
     *
     * @return array
     */
    public function detalhe()
    {
        if (is_numeric($this->idbai) && is_numeric($this->idlog) && is_numeric($this->cep)) {
            $db = new clsBanco();
            $db->Consulta("SELECT {$this->_todos_campos} FROM {$this->_tabela} clb WHERE clb.idbai = '{$this->idbai}' AND clb.idlog = '{$this->idlog}' AND clb.cep = '{$this->cep}'");
            $db->ProximoRegistro();

            return $db->Tupla();
        }

        return false;
    }

    /**
     * Retorna true se o registro existir. Caso contrário retorna false.
     *
     * @return bool
     */
    public function existe()
    {
        if (is_numeric($this->idbai) && is_numeric($this->idlog) && is_numeric($this->cep)) {
            $db = new clsBanco();
            $db->Consulta("SELECT 1 FROM {$this->_tabela} WHERE idbai = '{$this->idbai}' AND idlog = '{$this->idlog}' AND cep = '{$this->cep}'");
            if ($db->ProximoRegistro()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna true se o registro existir. Caso contrário retorna false.
     *
     * @return bool
     */
    public function listaLogradouro($idbai = null)
    {
        if (is_numeric($idbai)) {
            $db = new clsBanco();
            $db->Consulta("
                SELECT
                    DISTINCT clb.idlog
                    , l.nome
                FROM
                    urbano.cep_logradouro_bairro clb
                    , public.logradouro l
                WHERE
                    clb.idbai = '{$idbai}'
                    AND clb.idlog = l.idlog
                ORDER BY
                    l.nome ASC
                ");

            while ($db->ProximoRegistro()) {
                $resultado[] = $db->Tupla();
            }

            return $resultado;
        }

        return false;
    }

    /**
     * Exclui um registro
     *
     * @return bool
     */
    public function excluir()
    {
        if (is_numeric($this->idbai) && is_numeric($this->idlog) && is_numeric($this->cep)) {
            $db = new clsBanco();
            $db->Consulta("DELETE FROM {$this->_tabela} WHERE idbai = '{$this->idbai}' AND idlog = '{$this->idlog}' AND cep = '{$this->cep}'");

            return true;
        }

        return false;
    }

    /**
     * Exclui todos os registros
     *
     * @return bool
     */
    public function excluirTodos($idlog)
    {
        if (is_numeric($idlog)) {
            $db = new clsBanco();
            $db->Consulta("DELETE FROM {$this->_tabela} WHERE idlog = '{$idlog}'");

            return true;
        }

        return false;
    }
}
