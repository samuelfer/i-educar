<?php
// error_reporting(E_ERROR);
// ini_set("display_errors", 1);
/**
 * i-Educar - Sistema de gestão escolar
 *
 * Copyright (C) 2006  Prefeitura Municipal de Itajaí
 *     <ctima@itajai.sc.gov.br>
 *
 * Este programa é software livre; você pode redistribuí-lo e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU conforme publicada pela Free
 * Software Foundation; tanto a versão 2 da Licença, como (a seu critério)
 * qualquer versão posterior.
 *
 * Este programa é distribuí­do na expectativa de que seja útil, porém, SEM
 * NENHUMA GARANTIA; nem mesmo a garantia implí­cita de COMERCIABILIDADE OU
 * ADEQUAÇÃO A UMA FINALIDADE ESPECÍFICA. Consulte a Licença Pública Geral
 * do GNU para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral do GNU junto
 * com este programa; se não, escreva para a Free Software Foundation, Inc., no
 * endereço 59 Temple Street, Suite 330, Boston, MA 02111-1307 USA.
 *
 * @author    Lucas Schmoeller da Silva <lucas@portabilis.com.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   Api
 * @subpackage  Modules
 * @since   Arquivo disponível desde a versão ?
 * @version   $Id$
 */

require_once 'Portabilis/Controller/ApiCoreController.php';
require_once 'Avaliacao/Service/Boletim.php';
require_once 'Avaliacao/Model/NotaComponenteDataMapper.php';
require_once 'Avaliacao/Model/FaltaComponenteDataMapper.php';
require_once 'Avaliacao/Model/FaltaGeralDataMapper.php';
require_once 'Avaliacao/Model/ParecerDescritivoComponenteDataMapper.php';
require_once 'Avaliacao/Model/ParecerDescritivoGeralDataMapper.php';
require_once 'RegraAvaliacao/Model/TipoPresenca.php';
require_once 'RegraAvaliacao/Model/TipoParecerDescritivo.php';

require_once 'Portabilis/String/Utils.php';

class DiarioController extends ApiCoreController
{
  protected $_processoAp        = 642;

  protected function getRegra($turmaId) {
    return App_Model_IedFinder::getRegraAvaliacaoPorTurma($turmaId);
  }

  protected function getComponentesPorMatricula($matriculaId) {
    return App_Model_IedFinder::getComponentesPorMatricula($matriculaId);
  }

  protected function getComponentesPorTurma($turmaId) {
    $objTurma = new clsPmieducarTurma($turmaId);
    $detTurma = $objTurma->detalhe();
    $escolaId = $detTurma["ref_ref_cod_escola"];
    $serieId = $detTurma["ref_ref_cod_serie"];
    return App_Model_IedFinder::getComponentesTurma($serieId, $escolaId, $turmaId);
  }

  protected function validateComponenteCurricular($matriculaId, $componenteCurricularId){

    $componentes = $this->getComponentesPorMatricula($matriculaId);
    $componentes = CoreExt_Entity::entityFilterAttr($componentes, 'id', 'id');
    $valid = in_array($componenteCurricularId, $componentes);
    if(!$valid){
      throw new CoreExt_Exception(Portabilis_String_Utils::toLatin1("Componente curricular de código $componenteCurricularId não existe para essa turma/matrícula."));
    }
    return $valid;
  }

  protected function validateComponenteTurma($turmaId, $componenteCurricularId){

    $componentesTurma = $this->getComponentesPorTurma($turmaId);
    $componentesTurma = CoreExt_Entity::entityFilterAttr($componentesTurma, 'id', 'id');
    $valid = in_array($componenteCurricularId, $componentesTurma);
    if(!$valid){
      throw new CoreExt_Exception(Portabilis_String_Utils::toLatin1("Componente curricular de código $componenteCurricularId não existe para a turma $turmaId ."));
    }
    return $valid;
  }

  protected function trySaveServiceBoletim($turmaId, $alunoId) {
    try {
      $this->serviceBoletim($turmaId, $alunoId)->save();
    }
    catch (CoreExt_Service_Exception $e) {
      // excecoes ignoradas :( pois servico lanca excecoes de alertas, que não são exatamente erros.
      // error_log('CoreExt_Service_Exception ignorada: ' . $e->getMessage());
    }
  }

  protected function findMatriculaByTurmaAndAluno($turmaId, $alunoId){
    $resultado = array();

    $sql = 'SELECT m.cod_matricula AS id
              FROM pmieducar.matricula m
              INNER JOIN pmieducar.matricula_turma mt ON m.cod_matricula = mt.ref_cod_matricula
              WHERE m.ativo = 1
              AND  mt.ref_cod_turma = $1
              AND m.ref_cod_aluno = $2
              AND m.aprovado IN (1,2,3,13,12,14) -- PERMITIDO SOMENTE LANÇAR NOTAS PARA SITUAÇÕES APROVADO/REPROVADO/ANDAMENTO
            ORDER BY m.aprovado
              LIMIT 1';

    $matriculaId = $this->fetchPreparedQuery($sql, array($turmaId, $alunoId), true, 'first-field');

    return $matriculaId;
  }

  protected function serviceBoletim($turmaId, $alunoId) {
    $matriculaId = $this->findMatriculaByTurmaAndAluno($turmaId, $alunoId);
    if($matriculaId){

      if (! isset($this->_boletimServiceInstances))
        $this->_boletimServiceInstances = array();

      // set service
      if (! isset($this->_boletimServiceInstances[$matriculaId])) {
        try {
          $params = array('matricula' => $matriculaId);
          $this->_boletimServiceInstances[$matriculaId] = new Avaliacao_Service_Boletim($params);
        }
        catch (Exception $e){
          $this->messenger->append(Portabilis_String_Utils::toLatin1("Erro ao instanciar serviço boletim para matricula {$matriculaId}: ") . $e->getMessage(), 'error', true);
        }
      }

      // validates service
      if (is_null($this->_boletimServiceInstances[$matriculaId]))
        throw new CoreExt_Exception(Portabilis_String_Utils::toLatin1("Não foi possivel instanciar o serviço boletim para a matrícula $matriculaId."));

      return $this->_boletimServiceInstances[$matriculaId];
    }else{
      return false;
    }
  }

  protected function canPostNotas(){
    return $this->validatesPresenceOf('notas') && $this->validatesPresenceOf('etapa');
  }

  protected function canPostFaltasPorComponente(){
    return $this->validatesPresenceOf('faltas') && $this->validatesPresenceOf('etapa');
  }

  protected function canPostFaltasGeral(){
    return $this->validatesPresenceOf('faltas') && $this->validatesPresenceOf('etapa');
  }

  protected function canPostPareceresPorEtapaComponente(){
    return $this->validatesPresenceOf('pareceres') && $this->validatesPresenceOf('etapa');
  }

  protected function canPostPareceresAnualPorComponente(){
    return $this->validatesPresenceOf('pareceres');
  }

  protected function canPostPareceresAnualGeral(){
    return $this->validatesPresenceOf('pareceres');
  }

  protected function canPostPareceresPorEtapaGeral(){
    return $this->validatesPresenceOf('pareceres') && $this->validatesPresenceOf('etapa');
  }

  protected function postNotas(){
    if($this->canPostNotas()){
      $etapa = $this->getRequest()->etapa;
      $notas = $this->getRequest()->notas;

      foreach ($notas as $turmaId => $notaTurma) {

        foreach($notaTurma as $alunoId => $notaTurmaAluno){

          foreach ($notaTurmaAluno as $componenteCurricularId => $notaTurmaAlunoDisciplina){
            if($this->validateComponenteTurma($turmaId, $componenteCurricularId)){
              $valor = $notaTurmaAlunoDisciplina['nota'];
              $notaRecuperacao = $notaTurmaAlunoDisciplina['recuperacao'];
              $nomeCampoRecuperacao = $this->defineCampoTipoRecuperacao($turmaId);
              $valor = $this->truncate($valor, 4);

              if($notaRecuperacao > $valor){
                $novaNota = $notaRecuperacao;
              }else{
                $novaNota = $valor;
              }

              $array_nota = array(
                    'componenteCurricular' => $componenteCurricularId,
                    'nota'                 => $novaNota,
                    'etapa'                => $etapa,
                    'notaOriginal'         => $valor);

              if(!empty($nomeCampoRecuperacao)){
                $array_nota[$nomeCampoRecuperacao] = $notaRecuperacao;
              }

              $nota = new Avaliacao_Model_NotaComponente($array_nota);

              if($this->serviceBoletim($turmaId, $alunoId)){
                $this->serviceBoletim($turmaId, $alunoId)->addNota($nota);
                $this->trySaveServiceBoletim($turmaId, $alunoId);
              }
            }
          }
        }
        $this->messenger->append('Notas postadas com sucesso!', 'success');
      }
    }
  }

  protected function postRecuperacoes(){
    if($this->canPostNotas()){
      $etapa = $this->getRequest()->etapa;
      $notas = $this->getRequest()->notas;

      foreach ($notas as $turmaId => $notaTurma) {

        foreach($notaTurma as $alunoId => $notaTurmaAluno){

          foreach ($notaTurmaAluno as $componenteCurricularId => $notaTurmaAlunoDisciplina){
            if($this->validateComponenteTurma($turmaId, $componenteCurricularId)){
              $valor = $notaTurmaAlunoDisciplina['nota'];
              $notaRecuperacao = $notaTurmaAlunoDisciplina['recuperacao'];
              $nomeCampoRecuperacao = $this->defineCampoTipoRecuperacao($turmaId);

              if($notaRecuperacao > $valor){
                $novaNota = $notaRecuperacao;
              }else{
                $novaNota = $valor;
              }

              $valor = $this->truncate($valor, 4);
              $array_nota = array(
                    'componenteCurricular' => $componenteCurricularId,
                    'nota'                 => $novaNota,
                    'etapa'                => $etapa,
                    'notaOriginal'         => $valor,
                    $nomeCampoRecuperacao  => $notaRecuperacao);

              $nota = new Avaliacao_Model_NotaComponente($array_nota);

              if($this->serviceBoletim($turmaId, $alunoId)){
                $this->serviceBoletim($turmaId, $alunoId)->addNota($nota);
                $this->trySaveServiceBoletim($turmaId, $alunoId);
              }
            }
          }
        }
        $this->messenger->append('Recuperacoes postadas com sucesso!', 'success');
      }
    }
  }

  private function defineCampoTipoRecuperacao($turmaId){
    $regra = $this->getRegra($turmaId);
    $campoRecuperacao = '';
    switch ($regra->get('tipoRecuperacaoParalela')) {
      case RegraAvaliacao_Model_TipoRecuperacaoParalela::USAR_POR_ETAPA:
        $campoRecuperacao = 'notaRecuperacaoParalela';
        break;

      case RegraAvaliacao_Model_TipoRecuperacaoParalela::USAR_POR_ETAPAS_ESPECIFICAS:
        $campoRecuperacao = 'notaRecuperacaoEspecifica';
        break;
    }

    return $campoRecuperacao;
  }


  protected function postFaltasPorComponente(){
    if($this->canPostFaltasPorComponente()){
      $etapa = $this->getRequest()->etapa;
      $faltas = $this->getRequest()->faltas;

      foreach ($faltas as $turmaId => $faltaTurma) {
        if($this->getRegra($turmaId)->get('tipoPresenca') != RegraAvaliacao_Model_TipoPresenca::POR_COMPONENTE){
          throw new CoreExt_Exception(Portabilis_String_Utils::toLatin1("A regra da turma $turmaId não permite lançamento de faltas por componente."));
        }
        foreach ($faltaTurma as $alunoId => $faltaTurmaAluno) {

          foreach ($faltaTurmaAluno as $componenteCurricularId => $faltaTurmaAlunoDisciplina){
            $matriculaId = $this->findMatriculaByTurmaAndAluno($turmaId, $alunoId);
            if($matriculaId){

              if($this->validateMatricula($matriculaId)){

                if($this->validateComponenteTurma($turmaId, $componenteCurricularId)){
                  $valor = $faltaTurmaAlunoDisciplina["valor"];

                  $falta = new Avaliacao_Model_FaltaComponente(array(
                    'componenteCurricular' => $componenteCurricularId,
                    'quantidade'           => $valor,
                    'etapa'                => $etapa
                  ));

                  $this->serviceBoletim($turmaId, $alunoId)->addFalta($falta);
                  $this->trySaveServiceBoletim($turmaId, $alunoId);
                }
              }
            }
          }
        }
      }

      $this->messenger->append('Faltas postadas com sucesso!', 'success');
    }
  }

  protected function postFaltasGeral(){
    if($this->canPostFaltasPorComponente()){
      $etapa = $this->getRequest()->etapa;
      $faltas = $this->getRequest()->faltas;

      foreach ($faltas as $turmaId => $faltaTurma) {
        if($this->getRegra($turmaId)->get('tipoPresenca') != RegraAvaliacao_Model_TipoPresenca::GERAL){
          throw new CoreExt_Exception(Portabilis_String_Utils::toLatin1("A regra da turma $turmaId não permite lançamento de faltas geral."));
        }

        foreach ($faltaTurma as $alunoId => $faltaTurmaAluno) {
          $faltas = $faltaTurmaAluno['valor'];

          if($this->findMatriculaByTurmaAndAluno($turmaId, $alunoId)){

            $falta = new Avaliacao_Model_FaltaGeral(array(
              'quantidade'           => $faltas,
              'etapa'                => $etapa
            ));

            $this->serviceBoletim($turmaId, $alunoId)->addFalta($falta);
            $this->trySaveServiceBoletim($turmaId, $alunoId);
          }
        }
      }

      $this->messenger->append('Faltas postadas com sucesso!', 'success');
    }
  }

  protected function postPareceresPorEtapaComponente(){
    if($this->canPostPareceresPorEtapaComponente()){
      $pareceres = $this->getRequest()->pareceres;
      $etapa = $this->getRequest()->etapa;

      foreach ($pareceres as $turmaId => $parecerTurma) {
        if($this->getRegra($turmaId)->get('parecerDescritivo') != RegraAvaliacao_Model_TipoParecerDescritivo::ETAPA_COMPONENTE){
          throw new CoreExt_Exception(Portabilis_String_Utils::toLatin1("A regra da turma $turmaId não permite lançamento de pareceres por etapa e componente."));
        }

        foreach ($parecerTurma as $alunoId => $parecerTurmaAluno) {
          if($this->findMatriculaByTurmaAndAluno($turmaId, $alunoId)){

            foreach ($parecerTurmaAluno as $componenteCurricularId => $parecerTurmaAlunoComponente) {
              if($this->validateComponenteTurma($turmaId, $componenteCurricularId)){

                $parecer = $parecerTurmaAlunoComponente['valor'];

                $parecerDescritivo = new Avaliacao_Model_ParecerDescritivoComponente(array(
                  'componenteCurricular' => $componenteCurricularId,
                  'parecer'              => Portabilis_String_Utils::toLatin1($parecer),
                  'etapa'                => $etapa
                ));

                $this->serviceBoletim($turmaId, $alunoId)->addParecer($parecerDescritivo);
                $this->trySaveServiceBoletim($turmaId, $alunoId);
              }
            }
          }
        }
      }

      $this->messenger->append('Pareceres postados com sucesso!', 'success');
    }
  }

  protected function postPareceresAnualPorComponente(){
    if($this->canPostPareceresAnualPorComponente()){
      $pareceres = $this->getRequest()->pareceres;

      foreach ($pareceres as $turmaId => $parecerTurma) {
        if($this->getRegra($turmaId)->get('parecerDescritivo') != RegraAvaliacao_Model_TipoParecerDescritivo::ANUAL_COMPONENTE){
          throw new CoreExt_Exception(Portabilis_String_Utils::toLatin1("A regra da turma $turmaId não permite lançamento de pareceres anual por componente."));
        }

        foreach ($parecerTurma as $alunoId => $parecerTurmaAluno) {
          if($this->findMatriculaByTurmaAndAluno($turmaId, $alunoId)){

            foreach ($parecerTurmaAluno as $componenteCurricularId => $parecerTurmaAlunoComponente) {
              if($this->validateComponenteCurricular($matriculaId, $componenteCurricularId)){

                $parecer = $parecerTurmaAlunoComponente['valor'];

                $parecerDescritivo = new Avaliacao_Model_ParecerDescritivoComponente(array(
                  'componenteCurricular' => $componenteCurricularId,
                  'parecer'              => Portabilis_String_Utils::toLatin1($parecer)
                ));

                $this->serviceBoletim($turmaId, $alunoId)->addParecer($parecerDescritivo);
                $this->trySaveServiceBoletim($turmaId, $alunoId);
              }
            }
          }
        }
      }

      $this->messenger->append('Pareceres postados com sucesso!', 'success');
    }
  }

  protected function postPareceresPorEtapaGeral(){
    if($this->canPostPareceresPorEtapaGeral()){
      $pareceres = $this->getRequest()->pareceres;
      $etapa = $this->getRequest()->etapa;

      foreach ($pareceres as $turmaId => $parecerTurma) {
        if($this->getRegra($turmaId)->get('parecerDescritivo') != RegraAvaliacao_Model_TipoParecerDescritivo::ETAPA_GERAL){
          throw new CoreExt_Exception(Portabilis_String_Utils::toLatin1("A regra da turma $turmaId não permite lançamento de pareceres por etapa geral."));
        }

        foreach ($parecerTurma as $alunoId => $parecerTurmaAluno) {
          if($this->findMatriculaByTurmaAndAluno($turmaId, $alunoId)){
            $parecer = $parecerTurmaAluno['valor'];

            $parecerDescritivo = new Avaliacao_Model_ParecerDescritivoGeral(array(
              'parecer'           => Portabilis_String_Utils::toLatin1($parecer),
              'etapa'             => $etapa
            ));

            $this->serviceBoletim($turmaId, $alunoId)->addParecer($parecerDescritivo);
            $this->trySaveServiceBoletim($turmaId, $alunoId);
          }
        }
      }

      $this->messenger->append('Pareceres postados com sucesso!', 'success');
    }
  }

  protected function postPareceresAnualGeral(){
    if($this->canPostPareceresAnualGeral()){
      $pareceres = $this->getRequest()->pareceres;

      foreach ($pareceres as $turmaId => $parecerTurma) {
        if($this->getRegra($turmaId)->get('parecerDescritivo') != RegraAvaliacao_Model_TipoParecerDescritivo::ANUAL_GERAL){
          throw new CoreExt_Exception(Portabilis_String_Utils::toLatin1("A regra da turma $turmaId não permite lançamento de pareceres anual geral."));
        }

        foreach ($parecerTurma as $alunoId => $parecerTurmaAluno) {
          $parecer = $parecerTurmaAluno['valor'];
          if($this->findMatriculaByTurmaAndAluno($turmaId, $alunoId)){

            $parecerDescritivo = new Avaliacao_Model_ParecerDescritivoGeral(array(
              'parecer' => Portabilis_String_Utils::toLatin1($parecer)
            ));

            $this->serviceBoletim($turmaId, $alunoId)->addParecer($parecerDescritivo);
            $this->trySaveServiceBoletim($turmaId, $alunoId);
          }
        }
      }

      $this->messenger->append('Pareceres postados com sucesso!', 'success');
    }
  }

  protected function validateMatricula($matriculaId){

    $ativo = false;

    if(!empty($matriculaId)){
      $sql = "SELECT m.ativo as ativo
                FROM pmieducar.matricula m
               WHERE m.cod_matricula = $1
               LIMIT 1";

      $ativo = $this->fetchPreparedQuery($sql, array($matriculaId), true, 'first-field');
    }

    return $ativo;
  }

  private function truncate($val, $f="0"){
    if(($p = strpos($val, '.')) !== false) {
        $val = floatval(substr($val, 0, $p + 1 + $f));
    }
    return $val;
  }

  public function Gerar() {
    if ($this->isRequestFor('post', 'notas'))
      $this->appendResponse($this->postNotas());
    elseif ($this->isRequestFor('post', 'recuperacoes'))
      $this->appendResponse($this->postRecuperacoes());
    elseif ($this->isRequestFor('post', 'faltas-por-componente'))
      $this->appendResponse($this->postFaltasPorComponente());
    elseif ($this->isRequestFor('post', 'faltas-geral'))
      $this->appendResponse($this->postFaltasGeral());
    elseif ($this->isRequestFor('post', 'pareceres-por-etapa-e-componente'))
      $this->appendResponse($this->postPareceresPorEtapaComponente());
    elseif ($this->isRequestFor('post', 'pareceres-por-etapa-geral'))
      $this->appendResponse($this->postPareceresPorEtapaGeral());
    elseif ($this->isRequestFor('post', 'pareceres-anual-por-componente'))
      $this->appendResponse($this->postPareceresAnualPorComponente());
    elseif ($this->isRequestFor('post', 'pareceres-anual-geral'))
      $this->appendResponse($this->postPareceresAnualGeral());
    else
      $this->notImplementedOperationError();
  }
}
