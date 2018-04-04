<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class polizas_model extends CI_Model {
  private $empresaId;

  function __construct()
  {
    parent::__construct();
  }

  public function getCuentaIvaTrasladado($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND nivel = 4 AND nombre like 'IVA TRASLADADO'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%IVA TRASLADADO COBRADO%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=" AND UPPER(nombre) LIKE '%IVA TRASLADADO COBRADO%'"; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like 'IVA TRASLADADO'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like 'IVA TRASLADADO'"; //mamita
    else{
      $sql=" AND nivel = 4 AND nombre like 'IVA TRASLADADO'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IvaTrasladado'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIvaXTrasladar($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND nivel = 4 AND nombre like 'IVA X TRASLADAR'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%IVA X TRASLADAR COBRADO%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=" AND UPPER(nombre) LIKE '%IVA X TRASLADAR COBRADO%'"; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like 'IVA X TRASLADAR'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like 'IVA X TRASLADAR'"; //mamita
    else{
      $sql=" AND nivel = 4 AND nombre like 'IVA X TRASLADAR'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IvaXTrasladar'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIvaRetCobradoAc($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 39 AND nivel = 4 AND nombre like 'IVA RETENIDO COBRADO'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%RETENCION DE IVA%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like 'IVA RETENIDO COBRADO'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like 'IVA RETENIDO COBRADO'"; //mamita
    else{
      $sql=" AND id_padre = 39 AND nivel = 4 AND nombre like 'IVA RETENIDO COBRADO'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IvaRetCobradoAc'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIvaRetXCobrarAc($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 39 AND nivel = 4 AND nombre like 'IVA RETENIDO X COBRAR'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%RETENCION DE IVA X COBRAR%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like 'IVA RETENIDO X COBRAR'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like 'IVA RETENIDO X COBRAR'"; //mamita
    else{
      $sql=" AND id_padre = 39 AND nivel = 4 AND nombre like 'IVA RETENIDO X COBRAR'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IvaRetXCobrarAc'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaNCVenta($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1251 AND nombre like '%REBAJAS Y BONIFICA%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%REBAJAS Y BONIFICA%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%REBAJAS Y BONIFICA%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%REBAJAS Y BONIFICA%'"; //mamita
    else{
      $sql=" AND id_padre = 1251 AND nombre like '%REBAJAS Y BONIFICA%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'NCVenta'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIvaXAcreditar($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 231 AND nivel = 4 AND nombre like '%IVA ACREDITABLE PO%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%IVA ACREDITABLE POR DIFERIR%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=" AND nombre like '%IVA ACREDITABLE POR DIFERIR%'"; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%IVA ACREDITABLE PO%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%IVA ACREDITABLE PO%'"; //mamita
    else{
      $sql=" AND id_padre = 231 AND nivel = 4 AND nombre like '%IVA ACREDITABLE PO%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IvaXAcreditar'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIvaAcreditado($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 231 AND nivel = 4 AND nombre like '%IVA ACREDITABLE PA%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%IVA ACREDITABLE PAGADO%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=" AND nombre like '%IVA ACREDITABLE PAGADO%'"; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%IVA ACREDITABLE PA%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%IVA ACREDITABLE PA%'"; //mamita
    else{
      $sql=" AND id_padre = 231 AND nivel = 4 AND nombre like '%IVA ACREDITABLE PA%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IvaAcreditado'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIvaRetXPagar100($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND nombre like '100% RETENCION IVA X PAGAR'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '100% RETENCION IVA X PAGAR'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=" AND id_empresa = {$this->empresaId} AND nombre like '100% RETENCION IVA X PAGAR'"; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '100% RETENCION IVA X PAGAR'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '100% RETENCION IVA X PAGAR'"; //mamita
    else{
      $sql=" AND nombre like '100% RETENCION IVA X PAGAR'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IvaRetXPagar100'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIvaRetPagado100($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND nombre like '100% RETENCION IVA PAGADO'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '100% RETENCION IVA PAGADO'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=" AND id_empresa = {$this->empresaId} AND nombre like '100% RETENCION IVA PAGADO'"; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '100% RETENCION IVA PAGADO'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '100% RETENCION IVA PAGADO'"; //mamita
    else{
      $sql=" AND nombre like '100% RETENCION IVA PAGADO'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IvaRetPagado100'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIvaRetXPagar($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1191 AND nivel = 4 AND nombre like '4% RETENCION IVA X PAGAR'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '4% RETENCION IVA X PAGAR'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=" AND id_empresa = {$this->empresaId} AND nombre like '4% RETENCION IVA X PAGAR'"; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '4% RETENCION IVA X PAGAR'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '4% RETENCION IVA X PAGAR'"; //mamita
    else{
      $sql=" AND id_padre = 1191 AND nivel = 4 AND nombre like '4% RETENCION IVA X PAGAR'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IvaRetXPagar'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIvaRetPagado($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1191 AND nivel = 4 AND nombre like '4% RETENCION IVA PAGADO'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '4% RETENCION IVA PAGADO'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=" AND id_empresa = {$this->empresaId} AND nombre like '4% RETENCION IVA PAGADO'"; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '4% RETENCION IVA PAGADO'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '4% RETENCION IVA PAGADO'"; //mamita
    else{
      $sql=" AND id_padre = 1191 AND nivel = 4 AND nombre like '4% RETENCION IVA PAGADO'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IvaRetPagado'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIvaRetXPagarHono($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1191 AND nivel = 4 AND nombre like '%IVA HONORARIO X PAGAR%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE 'IVA HONORARIO X PAGAR'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%IVA HONORARIO X PAGAR%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%IVA HONORARIO X PAGAR%'"; //mamita
    else{
      $sql=" AND id_padre = 1191 AND nivel = 4 AND nombre like '%IVA HONORARIO X PAGAR%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IvaRetXPagarHono'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIvaRetPagadoHono($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1191 AND nivel = 4 AND nombre like '%IVA HONORARIO PAGADO%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%IVA HONORARIO PAGADO%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%IVA HONORARIO PAGADO%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%IVA HONORARIO PAGADO%'"; //mamita
    else{
      $sql=" AND id_padre = 1191 AND nivel = 4 AND nombre like '%IVA HONORARIO PAGADO%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IvaRetPagadoHono'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIsrRetXPagarHono($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1191 AND nivel = 4 AND nombre like '%SOBRE HONORARIOS X PAGAR%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE 'SOBRE HONORARIOS X PAGAR'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%SOBRE HONORARIOS X PAGAR%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%SOBRE HONORARIOS X PAGAR%'"; //mamita
    else{
      $sql=" AND id_padre = 1191 AND nivel = 4 AND nombre like '%SOBRE HONORARIOS X PAGAR%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IsrRetXPagarHono'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIsrRetPagadoHono($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1191 AND nivel = 4 AND nombre like '%SOBRE HONORARIOS PAGADO%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%SOBRE HONORARIOS PAGADO%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%SOBRE HONORARIOS PAGADO%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%SOBRE HONORARIOS PAGADO%'"; //mamita
    else{
      $sql=" AND id_padre = 1191 AND nivel = 4 AND nombre like '%SOBRE HONORARIOS PAGADO%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IsrRetPagadoHono'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaIsrRetXPagar($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 19 AND nombre like '%ISR RETENIDO BANCA%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%ISR%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%ISR RETENIDO BANCA%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%ISR RETENIDO BANCA%'"; //mamita
    else{
      $sql=" AND id_padre = 19 AND nombre like '%ISR RETENIDO BANCA%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'IsrRetXPagar'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaNCGasto($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1276 AND nombre like '%REB. Y BONF. S/C%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%REBAJAS Y BONIFICA%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=" AND nombre like '%REBAJAS Y BONIFICACIONES'"; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%REB. Y BONF. S/C%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%REB. Y BONF. S/C%'"; //mamita
    else{
      $sql=" AND id_padre = 1276 AND nombre like '%REB. Y BONF. S/C%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'NCGasto'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaCuadreGasto()
  {
    if ($this->empresaId==2) $cuenta = '50000100'; //sanjorge
    elseif($this->empresaId==6) $cuenta = ''; //francis -
    elseif($this->empresaId==4) $cuenta=""; //Raul jorge
    elseif($this->empresaId==3) $cuenta = '50007000'; //Gomez gudiño
    elseif($this->empresaId==5) $cuenta=""; //vianey rocio
    elseif($this->empresaId==12) $cuenta="50000100"; //plasticos
    elseif($this->empresaId==14) $cuenta="50000100"; //mamita
    else{
      $cuenta = '50000100';
    }
    if (!isset($cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'CuadreGasto'")->row();
      $cuenta = (isset($data->cuenta)? $data->cuenta : '');
    }
    return $cuenta;
  }
  public function getCuentaNSueldo($basic=true, $departamento=null){
    $sql = '';
    if ($this->empresaId==2 && $departamento == 1) $sql=" AND id_padre = 1296 AND nombre like '%SUELDOS%'"; //sanjorge
    elseif($this->empresaId==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%SUELDOS%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%SUELDOS%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12 && $departamento == 1) $sql=" AND nombre like '%SUELDOS VENTAS%'"; //plasticos
    elseif($this->empresaId==14 && $departamento == 1) $sql=" AND nombre like '%SUELDOS VENTAS%'"; //mamita
    elseif($this->empresaId==12 && $departamento != 1) $sql=" AND nombre like '%SUELDOS PRODUCCION%'"; //plasticos
    elseif($this->empresaId==14 && $departamento != 1) $sql=" AND nombre like '%SUELDOS PRODUCCION%'"; //mamita
    else{
      $sql=" AND id_padre = 1296 AND nombre like '%SUELDOS%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $tipo_cuenta = $departamento == 1? 'NSueldo': 'NSueldoProd';
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = '{$tipo_cuenta}'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaNVacaciones($basic=true, $departamento=null){
    $sql = '';
    if ($this->empresaId==2 && $departamento == 1) $sql=" AND id_padre = 1296 AND nombre like '%VACACIONES%'"; //sanjorge
    elseif($this->empresaId==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%VACACIONES%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%VACACIONES%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12 && $departamento == 1) $sql=" AND nombre like '%VACACIONES VENTAS%'"; //plasticos
    elseif($this->empresaId==14 && $departamento == 1) $sql=" AND nombre like '%VACACIONES VENTAS%'"; //mamita
    elseif($this->empresaId==12 && $departamento != 1) $sql=" AND nombre like '%VACACIONES PRODUCCION%'"; //plasticos
    elseif($this->empresaId==14 && $departamento != 1) $sql=" AND nombre like '%VACACIONES PRODUCCION%'"; //mamita
    else{
      $sql=" AND id_padre = 1296 AND nombre like '%VACACIONES%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $tipo_cuenta = $departamento == 1? 'NVacaciones': 'NVacacionesProd';
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = '{$tipo_cuenta}'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaNPrimaVacacional($basic=true, $departamento=null){
    $sql = '';
    if ($this->empresaId==2 && $departamento == 1) $sql=" AND id_padre = 1296 AND nombre like '%PRIMA VACACIONAL%'"; //sanjorge
    elseif($this->empresaId==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%PRIMA VACACIONAL%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%PRIMA VACACIONAL%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12 && $departamento == 1) $sql=" AND nombre like '%PRIMA VACACIONAL VENTAS%'"; //plasticos
    elseif($this->empresaId==14 && $departamento == 1) $sql=" AND nombre like '%PRIMA VACACIONAL VENTAS%'"; //mamita
    elseif($this->empresaId==12 && $departamento != 1) $sql=" AND nombre like '%PRIMA VACACIONAL PRODUCCION%'"; //plasticos
    elseif($this->empresaId==14 && $departamento != 1) $sql=" AND nombre like '%PRIMA VACACIONAL PRODUCCION%'"; //mamita
    else{
      $sql=" AND id_padre = 1296 AND nombre like '%PRIMA VACACIONAL%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $tipo_cuenta = $departamento == 1? 'NPrimaVacacional': 'NPrimaVacacionalProd';
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = '{$tipo_cuenta}'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaNAguinaldo($basic=true, $departamento=null){
    $sql = '';
    if ($this->empresaId==2 && $departamento == 1) $sql=" AND id_padre = 1296 AND nombre like '%AGUINALDOS%'"; //sanjorge
    elseif($this->empresaId==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%AGUINALDO%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%AGUINALDOS%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12 && $departamento == 1) $sql=" AND nombre like '%AGUINALDOS VENTAS%'"; //plasticos
    elseif($this->empresaId==14 && $departamento == 1) $sql=" AND nombre like '%AGUINALDOS VENTAS%'"; //mamita
    elseif($this->empresaId==12 && $departamento != 1) $sql=" AND nombre like '%AGUINALDOS PRODUCCION%'"; //plasticos
    elseif($this->empresaId==14 && $departamento != 1) $sql=" AND nombre like '%AGUINALDOS PRODUCCION%'"; //mamita
    else{
      $sql=" AND id_padre = 1296 AND nombre like '%AGUINALDOS%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $tipo_cuenta = $departamento == 1? 'NAguinaldo': 'NAguinaldoProd';
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = '{$tipo_cuenta}'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaNHorasHex($basic=true, $departamento=null){
    $sql = '';
    if ($this->empresaId==2 && $departamento == 1) $sql=" AND id_padre = 1296 AND nombre like '%HORAS EXTRAS%'"; //sanjorge
    elseif($this->empresaId==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%COMPENSACION%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%HORAS EXTRAS%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12 && $departamento == 1) $sql=" AND nombre like '%COMPENSACION VENTAS%'"; //plasticos
    elseif($this->empresaId==14 && $departamento == 1) $sql=" AND nombre like '%COMPENSACION VENTAS%'"; //mamita
    elseif($this->empresaId==12 && $departamento != 1) $sql=" AND nombre like '%COMPENSACION PRODUCCION%'"; //plasticos
    elseif($this->empresaId==14 && $departamento != 1) $sql=" AND nombre like '%COMPENSACION PRODUCCION%'"; //mamita
    else{
      $sql=" AND id_padre = 1296 AND nombre like '%HORAS EXTRAS%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $tipo_cuenta = $departamento == 1? 'NHorasHex': 'NHorasHexProd';
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = '{$tipo_cuenta}'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  private function getPAsistenciaContpaq($basic=true, $departamento=1)
  {
    $sql = '';
    if ($this->empresaId==2 && $departamento == 1) $sql=" AND UPPER(nombre) LIKE '%ASISTENCIA%' AND id_padre = '1296'"; //sanjorge
    elseif($this->empresaId==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%ASISTENCIA%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND LOWER(nombre) LIKE '%ispt antes%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12 && $departamento == 1) $sql=" AND UPPER(nombre) like '%ASISTENCIA VENTAS%'"; //plasticos
    elseif($this->empresaId==14 && $departamento == 1) $sql=" AND UPPER(nombre) like '%ASISTENCIA VENTAS%'"; //mamita
    elseif($this->empresaId==12 && $departamento != 1) $sql=" AND UPPER(nombre) like '%ASISTENCIA PRODUCCION%'"; //plasticos
    elseif($this->empresaId==14 && $departamento != 1) $sql=" AND UPPER(nombre) like '%ASISTENCIA PRODUCCION%'"; //mamita
    else{
      $sql=" AND LOWER(nombre) LIKE '%ASISTENCIA%' AND id_padre = '1191'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $tipo_cuenta = $departamento == 1? 'NPAsistencia': 'NPAsistenciaProd';
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = '{$tipo_cuenta}'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
    // return (isset($data->cuenta)? $data->cuenta: '');
  }
  private function getNIndemnizacionesContpaq($basic=true, $departamento=1)
  {
    $sql = '';
    if ($this->empresaId==2 && $departamento == 1) $sql=" AND UPPER(nombre) LIKE '%INDEMNIZACIONES%' AND id_padre = '1296'"; //sanjorge
    elseif($this->empresaId==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%INDEMNIZACIONES%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND LOWER(nombre) LIKE '%ispt antes%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12 && $departamento == 1) $sql=" AND UPPER(nombre) like '%INDEMNIZACIONES%'"; //plasticos
    elseif($this->empresaId==14 && $departamento == 1) $sql=" AND UPPER(nombre) like '%INDEMNIZACIONES%'"; //mamita
    elseif($this->empresaId==12 && $departamento != 1) $sql=" AND UPPER(nombre) like '%INDEMNIZACIONES%'"; //plasticos
    elseif($this->empresaId==14 && $departamento != 1) $sql=" AND UPPER(nombre) like '%INDEMNIZACIONES%'"; //mamita
    else{
      $sql=" AND LOWER(nombre) LIKE '%INDEMNIZACIONES%' AND id_padre = '1191'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $tipo_cuenta = $departamento == 1? 'NIndemnizaciones': 'NIndemnizacionesProd';
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = '{$tipo_cuenta}'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaNominaPagar($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1163 AND nombre like '%NOMINAS POR PAGAR%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%NOMINAS POR PAGAR%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%NOMINAS POR PAGAR%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%NOMINAS POR PAGAR%'"; //mamita
    else{
      $sql=" AND id_padre = 1163 AND nombre like '%NOMINAS POR PAGAR%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'NominaPagar'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaNSubsidio($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 28 AND nombre like '%SUBSIDIO AL EMPLEO%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%SUBSIDIO AL EMPLEO%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%SUBSIDIO AL EMPLEO%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%SUBSIDIO AL EMPLEO%'"; //mamita
    else{
      $sql=" AND id_padre = 28 AND nombre like '%SUBSIDIO AL EMPLEO%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'NSubsidio'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaNImss($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1191 AND nombre like '%IMSS RETENIDO%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%IMSS RETENIDO%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%IMSS RETENIDO%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%IMSS RETENIDO%'"; //mamita
    else{
      $sql=" AND id_padre = 1191 AND nombre like '%IMSS RETENIDO%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'NImss'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaNVejez($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1191 AND nombre like '%CENSATIA Y VEJEZ%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%CENSATIA Y VEJEZ%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%CENSATIA Y VEJEZ%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%CENSATIA Y VEJEZ%'"; //mamita
    else{
      $sql=" AND id_padre = 1191 AND nombre like '%CENSATIA Y VEJEZ%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'NVejez'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaNInfonavit($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1191 AND nombre like '%CREDITO INFONAVIT%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%INFONAVIT%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%CREDITO INFONAVIT%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%CREDITO INFONAVIT%'"; //mamita
    else{
      $sql=" AND id_padre = 1191 AND nombre like '%CREDITO INFONAVIT%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'NInfonavit'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }
  public function getCuentaNIsr($basic=true){
    $sql = '';
    if ($this->empresaId==2) $sql=" AND id_padre = 1191 AND nombre like '%ISPT ANTES DEL SUB%'"; //sanjorge
    elseif($this->empresaId==6) $sql=" AND UPPER(nombre) LIKE '%ISPT ANTES DEL SUB%'"; //francis -
    elseif($this->empresaId==4) $sql=""; //Raul jorge
    elseif($this->empresaId==3) $sql=""; //Gomez gudiño
    elseif($this->empresaId==5) $sql=""; //vianey rocio
    elseif($this->empresaId==12) $sql=" AND nombre like '%ISPT ANTES DEL SUB%'"; //plasticos
    elseif($this->empresaId==14) $sql=" AND nombre like '%ISPT ANTES DEL SUB%'"; //mamita
    else{
      $sql=" AND id_padre = 1191 AND nombre like '%ISPT ANTES DEL SUB%'"; //tests carga las de sanjorge
    }
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} {$sql}")->row();
    if (!isset($data->cuenta)) {
      $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'NIsr'")->row();
    }
    return $basic? (isset($data->cuenta)? $data->cuenta : ''): $data;
  }


  public function setEspacios($texto, $posiciones, $direccion='l')
  {
    $len   = strlen($texto);
    $texto = $len>$posiciones? substr($texto, 0, $posiciones): $texto;
    $len   = strlen($texto);
    $faltante = $posiciones-$len;
    $espacios = '';
    if ($faltante > 0)
      for ($i=0; $i < $faltante; $i++)
        $espacios .= ' ';

    if($direccion=='l')
      $texto .= $espacios;
    else
      $texto = $espacios.$texto;

    return $texto.' ';
  }
  public function numero($numero)
  {
    $numero = str_replace(',', '', String::formatoNumero($numero, 2, '') );
    $num = explode('.', $numero);
    if(!isset($num[1]))
      $numero .= '.0';
    return $numero;
  }

   /**
    * OBTIENE EL FOLIO PARA LA POLIZA CON RANGOS DE ACUERDO AL TIPO
    * @return [type]
    */
  public function getFolio($tipo=null, $tipo2=null, $tipo3=null, $tipo22=null){
    $tipo  = $tipo!=null? $tipo: $this->input->post('tipo');
    $tipo2 = $tipo2!=null? $tipo2: $this->input->post('tipo2');
    $tipo3 = $tipo3!=null? $tipo3: $this->input->post('tipo3');
    $tipo22 = $tipo22!=null? $tipo22: $this->input->post('tipo22');

    $rangos = array(
      'diario_ventas'    => array(1, 50),
      'diario_ventas_nc' => array(51, 100),
      'diario_gastos'    => array(101, 200),
      'diario_productos' => array(101, 200),
      'nomina'           => array(201, 250),
      'ingresos'         => array(1, 1000),
      'egreso_limon'     => array(1, 299),
      'egreso_cheque'    => array(300, 599),
      'egreso_gasto'     => array(600, 1500),
      );

    $response = array('folio' => '', 'concepto' => '');
    $rango_sel = '';
    $sql = '';
    if ($tipo == '3') //Diarios
    {
      $response['concepto'] = "Gastos del dia ".String::fechaATexto(date("Y-m-d"));
      $rango_sel         = 'diario_gastos';
      if ($tipo2 == 'v')
      {
        $response['concepto'] = "Ventas del dia ".String::fechaATexto(date("Y-m-d"));
        $rango_sel         = 'diario_ventas';
      }elseif ($tipo2 == 'vnc')
      {
        $response['concepto'] = "Notas de Credito del dia ".String::fechaATexto(date("Y-m-d"));
        $rango_sel         = 'diario_ventas_nc';
      }elseif ($tipo2 == 'no')
      {
        $response['concepto'] = "Nomina ".String::fechaATexto(date("Y-m-d"));
        $rango_sel            = 'nomina';
      }elseif ($tipo2 == 'pr')
      {
        $result = $this->db->query("SELECT id_area, nombre
            FROM areas WHERE id_area = ".$tipo22)->row();
        $response['concepto'] = "Compra de {$result->nombre} al ".String::fechaATexto(date("Y-m-d"));
        $rango_sel            = 'diario_productos';
      }
      $sql = " AND tipo = {$tipo} AND tipo2 = '{$tipo2}'";
    }elseif ($tipo == '1') //Ingresos
    {
      $rango_sel = 'ingresos';
      $sql       = " AND tipo = {$tipo}";
    }elseif($tipo == '2'){ //Egresos = 2
      $response['concepto'] = "Egresos de limon, ".String::fechaATexto(date("Y-m-d"));
      $rango_sel            = 'egreso_limon';
      if ($tipo3 == 'ec')
      {
        $response['concepto'] = "Egresos de cheques, ".String::fechaATexto(date("Y-m-d"));
        $rango_sel            = 'egreso_cheque';
      }elseif ($tipo3 == 'eg')
      {
        $response['concepto'] = "Egresos de gastos, ".String::fechaATexto(date("Y-m-d"));
        $rango_sel            = 'egreso_gasto';
      }
      $sql       = " AND tipo = {$tipo} AND tipo2 = '{$tipo3}'";
    }

    $anio = date("Y"); $mes = date("m");
    $result = $this->db->query("SELECT * FROM polizas
                               WHERE extract(year FROM fecha) = '{$anio}' AND extract(month FROM fecha) = '{$mes}'
                                {$sql} ORDER BY id_poliza DESC LIMIT 1");
    $folio = $rangos[$rango_sel][0];
    if ($result->num_rows() > 0) {
      $row = $result->row();
      $folio = $row->folio+1;
    }

    if($folio > $rangos[$rango_sel][1])
      $folio = '';
    $response['folio'] = $folio;
    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LAS VENTAS
   * @return [type] [description]
   */
  public function polizaDiarioVentas()
  {
    $response = array('data' => '', 'facturas' => array(), 'folio' => '');
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    $fecha = $_GET['ffecha1'];
    if($_GET['ffecha1'] > $_GET['ffecha2'])
      $fecha = $_GET['ffecha2'];

    // if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
    //   $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
    //   $sql .= " AND Date(f.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    // }
    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $dias = abs(String::diasEntreFechas($_GET['ffecha1'], $_GET['ffecha2']))+1;

    $folio = $this->input->get('ffolio');
    for ($contador = 0; $contador < $dias; $contador++)
    {
      $sql_fecha = " AND Date(f.fecha) = '{$fecha}'";
      $query = $this->db->query(
        "SELECT id_factura
         FROM facturacion AS f
        WHERE status <> 'b' AND is_factura = 't'
            AND poliza_diario = 'f' AND id_nc IS NULL AND f.id_abono_factura IS NULL
           {$sql} {$sql_fecha}
        ORDER BY id_factura ASC
        ");

      if($query->num_rows() > 0)
      {
        $data = $query->result();
        $response['facturas'] = $data;

        $this->load->model('facturacion_model');

        $impuestos = array('iva_trasladar' => array('cuenta_cpi' => $this->getCuentaIvaXTrasladar(), 'importe' => 0, 'tipo' => '1'),
                           'iva_retenido' => array('cuenta_cpi' => $this->getCuentaIvaRetXCobrarAc(), 'importe' => 0, 'tipo' => '0'), );

        //Agregamos el header de la poliza
        $response['data'] .= $this->setEspacios('P',2).
                            $this->setEspacios(str_replace('-', '', $fecha),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                            $this->setEspacios($folio,9,'r').  //folio poliza
                            $this->setEspacios('1',1). //clase
                            $this->setEspacios('0',10). //iddiario
                            $this->setEspacios('Ventas del dia '.String::fechaATexto($fecha),100). //concepto
                            $this->setEspacios('11',2). //sistema de origen
                            $this->setEspacios('0',1). //impresa
                            $this->setEspacios('0',1)."\r\n"; //ajuste
        //Contenido de la Poliza
        foreach ($data as $key => $value)
        {
          $inf_factura = $this->facturacion_model->getInfoFactura($value->id_factura);

          if($inf_factura['info']->status == 'ca')
          {
            $cuenta_cpi = $inf_factura['info']->id_empresa==3? (count($inf_factura['productos'])>0? $inf_factura['productos'][0]->cuenta_cpi2: '40001000'): '41040000';
            $cuenta_cpi = $cuenta_cpi==''? '41040000': $cuenta_cpi;
            $cuenta_cpi = $inf_factura['info']->id_empresa==12? '41010000' : $cuenta_cpi;
            $response['data'] .= $this->setEspacios('M',2).
                              $this->setEspacios($cuenta_cpi,30).
                              $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                              $this->setEspacios('1',1).  //clientes es un abono = 1
                              $this->setEspacios( $this->numero(0) , 20).
                              $this->setEspacios('0',10).
                              $this->setEspacios('0.0',20).
                              $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio.' CANCELADA',100).
                              $this->setEspacios('',4)."\r\n";
          }else
          {
            //Colocamos el Cargo al Cliente de la factura
            $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                              $this->setEspacios($inf_factura['info']->cliente->cuenta_cpi,30).  //cuenta contpaq
                              $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).  //referencia movimiento
                              $this->setEspacios('0',1).  //tipo movimiento, clientes es un cargo = 0
                              $this->setEspacios( $this->numero($inf_factura['info']->total) , 20).  //importe movimiento - retencion
                              $this->setEspacios('0',10).  //iddiario poner 0
                              $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                              $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio, 100). //concepto
                              $this->setEspacios('',4)."\r\n"; //segmento de negocio

            $impuestos['iva_trasladar']['importe'] = 0;
            $impuestos['iva_retenido']['importe']  = 0;
            //Colocamos los Ingresos de la factura (41040000)
            foreach ($inf_factura['productos'] as $key => $value)
            {
              if ( ($inf_factura['info']->sin_costo == 't' && $value->id_clasificacion != '49' AND $value->id_clasificacion != '50' AND
                  $value->id_clasificacion != '51' AND $value->id_clasificacion != '52' AND
                  $value->id_clasificacion != '53') || $inf_factura['info']->sin_costo == 'f')
              {
                $value->cuenta_cpi = $inf_factura['info']->id_empresa==3? $value->cuenta_cpi2: $value->cuenta_cpi;

                $impuestos['iva_trasladar']['importe'] += $value->iva;
                $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
                $response['data'] .= $this->setEspacios('M',2).
                                $this->setEspacios(($value->cuenta_cpi!=''? $value->cuenta_cpi: '41040000'),30).
                                $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                                $this->setEspacios('1',1).  //clientes es un abono = 1
                                $this->setEspacios( $this->numero($value->importe) , 20).
                                $this->setEspacios('0',10).
                                $this->setEspacios('0.0',20).
                                $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100).
                                $this->setEspacios('',4)."\r\n";
              }
            }
            //Colocamos los impuestos de la factura
            foreach ($impuestos as $key => $impuesto)
            {
              if ($impuestos[$key]['importe'] > 0)
              {
                $response['data'] .= $this->setEspacios('M',2).
                              $this->setEspacios($impuesto['cuenta_cpi'],30).
                              $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                              $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                              $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                              $this->setEspacios('0',10).
                              $this->setEspacios('0.0',20).
                              $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100).
                              $this->setEspacios('',4)."\r\n";
              }
            }
          }
          unset($inf_factura);
        }
        $folio++;
      }
      $query->free_result();
      $fecha = String::suma_fechas($fecha, 1);
    }
    $response['folio'] = $folio-1;

    $response['data'] = mb_strtoupper($response['data'], 'UTF-8');

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LAS NOTAS DE CREDITO DE VENTAS
   * @return [type] [description]
   */
  public function polizaDiarioVentasNC()
  {
    $response = array('data' => '', 'facturas' => array(), 'folio' => '');
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    // if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
    //   $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
    //   $sql .= " AND Date(f.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    // }
    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $fecha = $_GET['ffecha1'];
    if($_GET['ffecha1'] > $_GET['ffecha2'])
      $fecha = $_GET['ffecha2'];

    $dias = abs(String::diasEntreFechas($_GET['ffecha1'], $_GET['ffecha2']))+1;

    $folio = $this->input->get('ffolio');
    for ($contador = 0; $contador < $dias; $contador++)
    {
      $sql_fecha = " AND Date(f.fecha) = '{$fecha}'";
      $query = $this->db->query(
        "SELECT id_factura
         FROM facturacion AS f
        WHERE status <> 'ca' AND status <> 'b' AND is_factura = 't'
            AND poliza_diario = 'f' AND id_nc IS NOT NULL
           {$sql} {$sql_fecha}
        ORDER BY id_factura ASC
        ");

      if($query->num_rows() > 0)
      {
        $data = $query->result();
        $response['facturas'] = $data;

        $this->load->model('facturacion_model');

        $impuestos = array('iva_trasladar' => array('cuenta_cpi' => $this->getCuentaIvaTrasladado(), 'importe' => 0, 'tipo' => '1'),
                           'iva_retenido' => array('cuenta_cpi' => $this->getCuentaIvaRetXCobrarAc(), 'importe' => 0, 'tipo' => '0'), );

        //Agregamos el header de la poliza
        $response['data'] .= $this->setEspacios('P',2).
                            $this->setEspacios(str_replace('-', '', $fecha),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                            $this->setEspacios($folio,9,'r').  //folio poliza
                            $this->setEspacios('1',1). //clase
                            $this->setEspacios('0',10). //iddiario
                            $this->setEspacios('Notas de Credito del dia '.String::fechaATexto($fecha),100). //concepto
                            $this->setEspacios('11',2). //sistema de origen
                            $this->setEspacios('0',1). //impresa
                            $this->setEspacios('0',1)."\r\n"; //ajuste
        //Contenido de la Poliza
        foreach ($data as $key => $value)
        {
          $inf_factura = $this->facturacion_model->getInfoFactura($value->id_factura);
          $inf_facturanc = $this->facturacion_model->getInfoFactura($inf_factura['info']->id_nc);

          $impuestos['iva_trasladar']['importe'] = 0;
          $impuestos['iva_retenido']['importe']  = 0;
          //Colocamos los Ingresos de la factura
          foreach ($inf_factura['productos'] as $key => $value)
          {
            $impuestos['iva_trasladar']['importe'] += $value->iva;
            $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
            $response['data'] .= $this->setEspacios('M',2).
                            $this->setEspacios($this->getCuentaNCVenta(), 30).  //cuenta nc ventas
                            $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                            $this->setEspacios('0',1).  //ingresos es un Cargos = 0
                            $this->setEspacios( $this->numero($value->importe) , 20).
                            $this->setEspacios('0',10).
                            $this->setEspacios('0.0',20).
                            $this->setEspacios('NC/'.$inf_factura['info']->serie.$inf_factura['info']->folio.' F/'.$inf_facturanc['info']->serie.$inf_facturanc['info']->folio,100).
                            $this->setEspacios('',4)."\r\n";
          }
          //Colocamos el Abono al Cliente de la factura
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($inf_factura['info']->cliente->cuenta_cpi,30).  //cuenta contpaq
                            $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).  //referencia movimiento
                            $this->setEspacios('1',1).  //tipo movimiento, clientes es un Abono = 1
                            $this->setEspacios( $this->numero($inf_factura['info']->total) , 20).  //importe movimiento - retencion
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios('NC/'.$inf_factura['info']->serie.$inf_factura['info']->folio.' F/'.$inf_facturanc['info']->serie.$inf_facturanc['info']->folio, 100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          //Colocamos los impuestos de la factura, negativos por nota de credito
          foreach ($impuestos as $key => $impuesto)
          {
            if ($impuestos[$key]['importe'] > 0)
            {
              $response['data'] .= $this->setEspacios('M',2).
                            $this->setEspacios($impuesto['cuenta_cpi'],30).
                            $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                            $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                            $this->setEspacios( '-'.$this->numero($impuesto['importe']) , 20).
                            $this->setEspacios('0',10).
                            $this->setEspacios('0.0',20).
                            $this->setEspacios('NC/'.$inf_factura['info']->serie.$inf_factura['info']->folio.' F/'.$inf_facturanc['info']->serie.$inf_facturanc['info']->folio,100).
                            $this->setEspacios('',4)."\r\n";
            }
          }
          unset($inf_factura);
        }
        $folio++;
      }
      $query->free_result();
      $fecha = String::suma_fechas($fecha, 1);
    }
    $response['folio'] = $folio-1;

    $response['data'] = mb_strtoupper($response['data'], 'UTF-8');

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LOS GASTOS, COMPRAS
   * @return [type] [description]
   */
  public function polizaDiarioGastos()
  {
    $response = array('data' => '', 'facturas' => array(), 'folio' => '');
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    // if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
    //   $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
    //   $sql .= " AND Date(f.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    //   $sql2 .= " AND Date(f.fecha_tara) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    // }
    if ($this->input->get('fid_empresa') != '')
    {
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";
      $sql2 .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";
    }

    $fecha = $_GET['ffecha1'];
    if($_GET['ffecha1'] > $_GET['ffecha2'])
      $fecha = $_GET['ffecha2'];

    $dias = abs(String::diasEntreFechas($_GET['ffecha1'], $_GET['ffecha2']))+1;

    $folio = $this->input->get('ffolio');
    for ($contador = 0; $contador < $dias; $contador++)
    {
      $sql_fecha1 = " AND Date(f.fecha) = '{$fecha}'";
      $query = $this->db->query(
        "SELECT id_compra
         FROM compras AS f
        WHERE status <> 'ca' AND id_nc IS NULL
            AND poliza_diario = 'f'
           {$sql} {$sql_fecha1}
        ORDER BY id_compra ASC
        ");
      //Gastos de limon
      // $query2 = $this->db->query(
      //   "SELECT id_bascula
      //    FROM bascula AS f
      //   WHERE status = 't' AND poliza_diario = 'f'
      //     AND tipo = 'en' AND accion = 'sa'
      //      {$sql2}
      //   ORDER BY id_bascula ASC
      //   ");

      if($query->num_rows() > 0) // || $query2->num_rows() > 0)
      {
        $data = $query->result();
        $response['facturas'] = $data;
        // $data2 = $query2->result();
        // $response['bascula'] = $data2;

        $this->load->model('compras_model');
        $this->load->model('bascula_model');

        $impuestos = array('iva_acreditar' => array('cuenta_cpi' => $this->getCuentaIvaXAcreditar(), 'importe' => 0, 'tipo' => '0'),
                           'iva_retenido' => array('cuenta_cpi' => $this->getCuentaIvaRetXPagar(), 'importe' => 0, 'tipo' => '1'),
                           'isr_retenido' => array('cuenta_cpi' => $this->getCuentaIsrRetXPagar(), 'importe' => 0, 'tipo' => '1'), );

        //Agregamos el header de la poliza
        $response['data'] .= $this->setEspacios('P',2).
                            $this->setEspacios(str_replace('-', '', $fecha),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                            $this->setEspacios($folio,9,'r').  //folio poliza
                            $this->setEspacios('1',1). //clase
                            $this->setEspacios('0',10). //iddiario
                            $this->setEspacios('Gastos del dia '.String::fechaATexto($fecha),100). //concepto
                            $this->setEspacios('11',2). //sistema de origen
                            $this->setEspacios('0',1). //impresa
                            $this->setEspacios('0',1)."\r\n"; //ajuste

        //Contenido de la Poliza de Compras
        foreach ($data as $key => $value)
        {
          $inf_compra = $this->compras_model->getInfoCompra($value->id_compra);


          $impuestos['iva_acreditar']['importe'] = 0;
          $impuestos['iva_retenido']['importe']  = 0;
          $impuestos['isr_retenido']['importe']  = 0;
          $productos_grups = array();
          //Colocamos los productos de la factura
          foreach ($inf_compra['productos'] as $key => $value)
          {
            $impuestos['iva_acreditar']['importe'] += $value->iva;
            $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
            $impuestos['isr_retenido']['importe']  += isset($value->retencion_isr)? $value->retencion_isr: 0;
            $value->cuenta_cpi = ($value->cuenta_cpi!=''? $value->cuenta_cpi: $this->getCuentaCuadreGasto() );

            if (array_key_exists($value->cuenta_cpi, $productos_grups))
            {
              $productos_grups[$value->cuenta_cpi]->importe       += $value->importe;
              $productos_grups[$value->cuenta_cpi]->iva           += $value->iva;
              $productos_grups[$value->cuenta_cpi]->retencion_iva += $value->retencion_iva;
              $productos_grups[$value->cuenta_cpi]->retencion_isr += isset($value->retencion_isr)? $value->retencion_isr: 0;
            }else
            {
              $productos_grups[$value->cuenta_cpi] = $value;
              $productos_grups[$value->cuenta_cpi]->retencion_isr  = isset($value->retencion_isr)? $value->retencion_isr: 0;
            }

            // $response['data'] .= $this->setEspacios('M',2).
            //                 $this->setEspacios($value->cuenta_cpi, 30).  //cuenta conpaq
            //                 $this->setEspacios('F/'.$inf_compra['info']->serie.$inf_compra['info']->folio,10).
            //                 $this->setEspacios('0',1).  //cargo, = 0
            //                 $this->setEspacios( $this->numero($value->importe) , 20).
            //                 $this->setEspacios('0',10).
            //                 $this->setEspacios('0.0',20).
            //                 $this->setEspacios($inf_compra['info']->proveedor->nombre_fiscal,100).
            //                 $this->setEspacios('',4)."\r\n";
          }

          foreach ($productos_grups as $key => $value)
          {
            if($value->importe > 0)
              $response['data'] .= $this->setEspacios('M',2).
                            $this->setEspacios($value->cuenta_cpi, 30).  //cuenta conpaq
                            $this->setEspacios('F/'.$inf_compra['info']->serie.$inf_compra['info']->folio,10).
                            $this->setEspacios('0',1).  //cargo, = 0
                            $this->setEspacios( $this->numero($value->importe) , 20).
                            $this->setEspacios('0',10).
                            $this->setEspacios('0.0',20).
                            $this->setEspacios($inf_compra['info']->proveedor->nombre_fiscal,100).
                            $this->setEspacios('',4)."\r\n";
          }
          //Colocamos los impuestos de la factura, negativos por nota de credito
          foreach ($impuestos as $key => $impuesto)
          {
            if ($impuestos[$key]['importe'] > 0)
            {
              $response['data'] .= $this->setEspacios('M',2).
                            $this->setEspacios($impuesto['cuenta_cpi'],30).
                            $this->setEspacios('F/'.$inf_compra['info']->serie.$inf_compra['info']->folio,10).
                            $this->setEspacios($impuesto['tipo'],1).  //de acuerdo al impuesto
                            $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                            $this->setEspacios('0',10).
                            $this->setEspacios('0.0',20).
                            $this->setEspacios($inf_compra['info']->proveedor->nombre_fiscal,100).
                            $this->setEspacios('',4)."\r\n";
            }
          }

          //Colocamos el Cargo al Proveedor de la factura
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($inf_compra['info']->proveedor->cuenta_cpi,30).  //cuenta contpaq
                            $this->setEspacios('F/'.$inf_compra['info']->serie.$inf_compra['info']->folio,10).  //referencia movimiento
                            $this->setEspacios('1',1).  //tipo movimiento, proveedor es un abono = 1
                            $this->setEspacios( $this->numero($inf_compra['info']->total) , 20).  //importe movimiento - retencion
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($inf_compra['info']->proveedor->nombre_fiscal, 100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio

          unset($inf_compra);
        }

        // //Contenido de la Poliza de Bascula
        // foreach ($data2 as $key => $value)
        // {
        //   $inf_compra = $this->bascula_model->getBasculaInfo($value->id_bascula);

        //   //Colocamos el Cargo al Proveedor de la factura
        //   $response['data'] .= $this->setEspacios('M',2). //movimiento = M
        //                     $this->setEspacios($inf_compra['info'][0]->cpi_proveedor,30).  //cuenta contpaq
        //                     $this->setEspacios($inf_compra['info'][0]->folio,10).  //referencia movimiento
        //                     $this->setEspacios('1',1).  //tipo movimiento, proveedor es un abono = 1
        //                     $this->setEspacios( $this->numero($inf_compra['info'][0]->importe) , 20).  //importe movimiento - retencion
        //                     $this->setEspacios('0',10).  //iddiario poner 0
        //                     $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
        //                     $this->setEspacios('Compra Boleta. '.$inf_compra['info'][0]->folio, 100). //concepto
        //                     $this->setEspacios('',4)."\r\n"; //segmento de negocio

        //   // $impuestos['iva_acreditar']['importe'] = 0;
        //   // $impuestos['iva_retenido']['importe']  = 0;
        //   //Colocamos los productos de la factura
        //   foreach ($inf_compra['cajas'] as $key => $value)
        //   {
        //     // $impuestos['iva_acreditar']['importe'] += $value->iva;
        //     // $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
        //     $response['data'] .= $this->setEspacios('M',2).
        //                     $this->setEspacios($value->cuenta_cpi, 30).  //cuenta conpaq
        //                     $this->setEspacios($inf_compra['info'][0]->folio,10).
        //                     $this->setEspacios('0',1).  //cargo, = 0
        //                     $this->setEspacios( $this->numero($value->importe) , 20).
        //                     $this->setEspacios('0',10).
        //                     $this->setEspacios('0.0',20).
        //                     $this->setEspacios('Compra Boleta. '.$inf_compra['info'][0]->folio,100).
        //                     $this->setEspacios('',4)."\r\n";
        //   }
        //   unset($inf_compra);
        // }
        $folio++;
      }
      $query->free_result();
      $fecha = String::suma_fechas($fecha, 1);
    }
    $response['folio'] = $folio-1;

    $response['data'] = mb_strtoupper($response['data'], 'UTF-8');

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LAS NOTAS DE CREDITO DE VENTAS
   * @return [type] [description]
   */
  public function polizaDiarioGastosNC()
  {
    $response = array('data' => '', 'facturas' => array(), 'folio' => '');
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    // if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
    //   $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
    //   $sql .= " AND Date(f.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    // }
    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $fecha = $_GET['ffecha1'];
    if($_GET['ffecha1'] > $_GET['ffecha2'])
      $fecha = $_GET['ffecha2'];

    $dias = abs(String::diasEntreFechas($_GET['ffecha1'], $_GET['ffecha2']))+1;

    $folio = $this->input->get('ffolio');
    for ($contador = 0; $contador < $dias; $contador++)
    {
      $sql_fecha = " AND Date(f.fecha) = '{$fecha}'";
      $query = $this->db->query(
        "SELECT id_compra
         FROM compras AS f
        WHERE status <> 'ca' AND status <> 'b'
            AND poliza_diario = 'f' AND id_nc IS NOT NULL
           {$sql} {$sql_fecha}
        ORDER BY id_compra ASC
        ");

      if($query->num_rows() > 0)
      {
        $data = $query->result();
        $response['facturas'] = $data;

        $this->load->model('compras_model');

        $impuestos = array('iva_acreditar' => array('cuenta_cpi' => $this->getCuentaIvaXAcreditar(), 'importe' => 0, 'tipo' => '1'),
                           'iva_retenido' => array('cuenta_cpi' => $this->getCuentaIvaRetXPagar(), 'importe' => 0, 'tipo' => '0'),
                           'isr_retenido' => array('cuenta_cpi' => $this->getCuentaIsrRetXPagar(), 'importe' => 0, 'tipo' => '0'), );

        //Agregamos el header de la poliza
        $response['data'] .= $this->setEspacios('P',2).
                            $this->setEspacios(str_replace('-', '', $fecha),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                            $this->setEspacios($folio,9,'r').  //folio poliza
                            $this->setEspacios('1',1). //clase
                            $this->setEspacios('0',10). //iddiario
                            $this->setEspacios('Notas de Credito del dia '.String::fechaATexto($fecha),100). //concepto
                            $this->setEspacios('11',2). //sistema de origen
                            $this->setEspacios('0',1). //impresa
                            $this->setEspacios('0',1)."\r\n"; //ajuste
        //Contenido de la Poliza
        foreach ($data as $key => $value)
        {
          $inf_factura = $this->compras_model->getInfoNotaCredito($value->id_compra);

          //Colocamos el Cargo al Cliente de la factura
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($inf_factura['info']->proveedor->cuenta_cpi,30).  //cuenta contpaq
                            $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, clientes es un cargo = 0
                            $this->setEspacios( $this->numero($inf_factura['info']->total) , 20).  //importe movimiento - retencion
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($inf_factura['info']->proveedor->nombre_fiscal, 100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio

          $impuestos['iva_acreditar']['importe'] = 0;
          $impuestos['iva_retenido']['importe']  = 0;
          $impuestos['isr_retenido']['importe']  = 0;
          //Colocamos los Ingresos de la factura
          foreach ($inf_factura['productos'] as $key => $value)
          {
            $impuestos['iva_acreditar']['importe'] += $value->iva;
            $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
            $impuestos['isr_retenido']['importe']  += isset($value->retencion_isr)? $value->retencion_isr: 0;
            $response['data'] .= $this->setEspacios('M',2).
                            $this->setEspacios($this->getCuentaNCGasto(), 30).  //cuenta nc ventas
                            $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                            $this->setEspacios('1',1).  //ingresos es un abono = 1
                            $this->setEspacios( $this->numero($value->importe) , 20).
                            $this->setEspacios('0',10).
                            $this->setEspacios('0.0',20).
                            $this->setEspacios($inf_factura['info']->proveedor->nombre_fiscal,100).
                            $this->setEspacios('',4)."\r\n";
          }
          //Colocamos los impuestos de la factura, negativos por nota de credito
          foreach ($impuestos as $key => $impuesto)
          {
            if ($impuestos[$key]['importe'] > 0)
            {
              $response['data'] .= $this->setEspacios('M',2).
                            $this->setEspacios($impuesto['cuenta_cpi'],30).
                            $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                            $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                            $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                            $this->setEspacios('0',10).
                            $this->setEspacios('0.0',20).
                            $this->setEspacios($inf_factura['info']->proveedor->nombre_fiscal,100).
                            $this->setEspacios('',4)."\r\n";
            }
          }
          unset($inf_factura);
        }
        $folio++;
      }
      $query->free_result();
      $fecha = String::suma_fechas($fecha, 1);
    }
    $response['folio'] = $folio-1;

    $response['data'] = mb_strtoupper($response['data'], 'UTF-8');

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LAS NOMINAS
   * @return [type] [description]
   */
  public function polizaDiarioNomina()
  {
    $response = array('data' => '', 'facturas' => array(), 'folio' => '');
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(f.fecha_inicio) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      $sql2 .= " AND Date(f.fecha_salida) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    $dias_desface = 4;
    if ($this->input->get('fid_empresa') != '') {
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";
      $sql2 .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";
      $dias_desface = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $_GET['fid_empresa'])->get()->row()->dia_inicia_semana;
    }

    $fecha = $_GET['ffecha1'];
    if($_GET['ffecha1'] > $_GET['ffecha2'])
      $fecha = $_GET['ffecha2'];

    $folio = $this->input->get('ffolio');

    $query = $this->db->query(
      "SELECT id_empleado, id_empresa, anio, semana, fecha_inicio, fecha_final, sueldo_semanal, vacaciones,
            prima_vacacional, aguinaldo, horas_extras, subsidio_pagado, subsidio, imss, infonavit, isr, total_neto, fondo_ahorro,
            vejez, id_departamente, pasistencia, departamento, indemnizaciones, tipo
      FROM (
        (
          SELECT f.id_empleado, f.id_empresa, f.anio, f.semana, Date(f.fecha_inicio) AS fecha_inicio, Date(f.fecha_final) AS fecha_final, f.sueldo_semanal, f.vacaciones,
              f.prima_vacacional, f.aguinaldo, f.horas_extras, f.subsidio_pagado, f.subsidio, f.imss, f.infonavit, f.isr, f.total_neto, f.fondo_ahorro,
              f.vejez, u.id_departamente, f.pasistencia, ud.nombre AS departamento,
              0 AS indemnizaciones, 'no' AS tipo
          FROM nomina_fiscal AS f
            INNER JOIN usuarios AS u ON u.id = f.id_empleado
            INNER JOIN usuarios_departamento AS ud ON ud.id_departamento = u.id_departamente
          WHERE f.esta_asegurado = 't'
             {$sql}
        )
        UNION
        (
          SELECT f.id_empleado, f.id_empresa, 0 AS anio, 0 AS semana, Date(now()) AS fecha_inicio, Date(f.fecha_salida) AS fecha_final, f.sueldo_semanal,
            f.vacaciones, f.prima_vacacional, f.aguinaldo, 0 AS horas_extras, 0 AS subsidio_pagado, f.subsidio, 0 AS imss, 0 AS infonavit,
            f.isr, f.total_neto, 0 AS fondo_ahorro, 0 AS vejez, u.id_departamente, 0 AS pasistencia, ud.nombre AS departamento,
            f.indemnizaciones, 'fi' AS tipo
          FROM finiquito AS f
            INNER JOIN usuarios AS u ON u.id = f.id_empleado
            INNER JOIN usuarios_departamento AS ud ON ud.id_departamento = u.id_departamente
          WHERE u.esta_asegurado = 't'
             {$sql2}
        )
      ) AS n
      ORDER BY id_empleado ASC, id_empresa ASC, semana ASC
      ");

    $nominas = array();
    foreach ($query->result() as $key => $value)
    {
      if ($value->tipo === 'fi') { // cuando es finiquito obtiene la semana y año
        $semana = String::obtenerSemanaDeFecha($value->fecha_final, $dias_desface);
        $value->anio = $semana['anio'];
        $value->semana = $semana['semana'];
        $value->fecha_inicio = $semana['fecha_inicio'];
        $value->fecha_final = $semana['fecha_final'];
      }

      if(isset($nominas[$value->id_empresa.$value->anio.$value->semana]))
      {
        if ($value->departamento == "ADMINISTRACION") {
          $nominas[$value->id_empresa.$value->anio.$value->semana]->sueldo_semanal1   += $value->sueldo_semanal;
          $nominas[$value->id_empresa.$value->anio.$value->semana]->vacaciones1       += $value->vacaciones;
          $nominas[$value->id_empresa.$value->anio.$value->semana]->prima_vacacional1 += $value->prima_vacacional;
          $nominas[$value->id_empresa.$value->anio.$value->semana]->aguinaldo1        += $value->aguinaldo;
          $nominas[$value->id_empresa.$value->anio.$value->semana]->horas_extras1     += $value->horas_extras;
          $nominas[$value->id_empresa.$value->anio.$value->semana]->pasistencia1      += $value->pasistencia;
          $nominas[$value->id_empresa.$value->anio.$value->semana]->indemnizaciones1  += $value->indemnizaciones;
        } else {
          $nominas[$value->id_empresa.$value->anio.$value->semana]->sueldo_semanal2   += $value->sueldo_semanal;
          $nominas[$value->id_empresa.$value->anio.$value->semana]->vacaciones2       += $value->vacaciones;
          $nominas[$value->id_empresa.$value->anio.$value->semana]->prima_vacacional2 += $value->prima_vacacional;
          $nominas[$value->id_empresa.$value->anio.$value->semana]->aguinaldo2        += $value->aguinaldo;
          $nominas[$value->id_empresa.$value->anio.$value->semana]->horas_extras2     += $value->horas_extras;
          $nominas[$value->id_empresa.$value->anio.$value->semana]->pasistencia2      += $value->pasistencia;
          $nominas[$value->id_empresa.$value->anio.$value->semana]->indemnizaciones2  += $value->indemnizaciones;
        }

        $nominas[$value->id_empresa.$value->anio.$value->semana]->subsidio        += $value->subsidio;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->imss            += $value->imss;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->vejez           += $value->vejez;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->infonavit       += $value->infonavit;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->isr             += $value->isr;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->total_neto      += $value->total_neto;
      }else{
        $value->fecha_inicio1    = $value->fecha_final;
        $value->fecha_inicio     = str_replace('-', '/', $value->fecha_inicio);
        $value->fecha_final      = str_replace('-', '/', $value->fecha_final);
        if ($value->departamento == "ADMINISTRACION") {
          $value->sueldo_semanal1   = $value->sueldo_semanal;
          $value->vacaciones1       = $value->vacaciones;
          $value->prima_vacacional1 = $value->prima_vacacional;
          $value->aguinaldo1        = $value->aguinaldo;
          $value->horas_extras1     = $value->horas_extras;
          $value->pasistencia1      = $value->pasistencia;
          $value->indemnizaciones1  = $value->indemnizaciones;

          $value->sueldo_semanal2   = 0;
          $value->vacaciones2       = 0;
          $value->prima_vacacional2 = 0;
          $value->aguinaldo2        = 0;
          $value->horas_extras2     = 0;
          $value->pasistencia2      = 0;
          $value->indemnizaciones2  = 0;
        } else {
          $value->sueldo_semanal1   = 0;
          $value->vacaciones1       = 0;
          $value->prima_vacacional1 = 0;
          $value->aguinaldo1        = 0;
          $value->horas_extras1     = 0;
          $value->pasistencia1      = 0;
          $value->indemnizaciones1  = 0;

          $value->sueldo_semanal2   = $value->sueldo_semanal;
          $value->vacaciones2       = $value->vacaciones;
          $value->prima_vacacional2 = $value->prima_vacacional;
          $value->aguinaldo2        = $value->aguinaldo;
          $value->horas_extras2     = $value->horas_extras;
          $value->pasistencia2      = $value->pasistencia;
          $value->indemnizaciones2  = $value->indemnizaciones;
        }
        $nominas[$value->id_empresa.$value->anio.$value->semana] = $value;
      }
    }

    if(count($nominas) > 0)
    {
      $response['facturas'] = $nominas;

      $this->load->model('facturacion_model');

      $sql2 = $sql3 = '';
      if ($this->input->get('fid_empresa') != '') {
        $sql2 .= " AND u.id_empresa = '".$_GET['fid_empresa']."'";
        $sql3 .= " AND nfp.id_empresa = '".$_GET['fid_empresa']."'";
      }

      //Contenido de la Poliza
      foreach ($nominas as $key => $value)
      {
        //Se obtienen los prestamos
        $prestamos = $this->db->query("SELECT u.id, u.cuenta_cpi, (u.apellido_paterno || ' ' || u.apellido_materno || ' ' || u.nombre) AS nombre, COALESCE(Sum(nfp.monto), 0) AS prestamo
                               FROM nomina_fiscal_prestamos AS nfp INNER JOIN usuarios AS u ON nfp.id_empleado = u.id
                               WHERE u.esta_asegurado = 't' AND nfp.anio = '{$value->anio}' AND nfp.semana = '{$value->semana}' {$sql2}
                               GROUP BY u.id")->result();

        //Se obtienen los fondos_ahorro
        $fondos_ahorro = $this->db->query("SELECT u.id, u.fondo_ahorro_cpi, (u.apellido_paterno || ' ' || u.apellido_materno || ' ' || u.nombre) AS nombre, COALESCE(Sum(nfp.fondo_ahorro), 0) AS fondo_ahorro
                               FROM nomina_fiscal AS nfp INNER JOIN usuarios AS u ON nfp.id_empleado = u.id
                               WHERE nfp.esta_asegurado = 't' AND nfp.anio = '{$value->anio}' AND nfp.semana = '{$value->semana}' {$sql3}
                               GROUP BY u.id HAVING COALESCE(Sum(nfp.fondo_ahorro), 0) > 0")->result();

        //Agregamos el header de la poliza
        $response['data'] .= $this->setEspacios('P',2).
                            $this->setEspacios(str_replace('-', '', $value->fecha_inicio1),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                            $this->setEspacios($folio,9,'r').  //folio poliza
                            $this->setEspacios('1',1). //clase
                            $this->setEspacios('0',10). //iddiario
                            $this->setEspacios("Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}",100). //concepto
                            $this->setEspacios('11',2). //sistema de origen
                            $this->setEspacios('0',1). //impresa
                            $this->setEspacios('0',1)."\r\n"; //ajuste

        //Colocamos el Cargo de la nomina
        for ($iper=1; $iper <= 2; $iper++) {
          if($value->{'sueldo_semanal'.$iper} > 0)
            $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($this->getCuentaNSueldo(true, $iper),30).  //cuenta contpaq
                            $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, clientes es un cargo = 0
                            $this->setEspacios( $this->numero(($value->{'sueldo_semanal'.$iper})) , 20).  //importe movimiento - retencion
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios("SUELDOS Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          if($value->{'vacaciones'.$iper} > 0)
            $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($this->getCuentaNVacaciones(true, $iper),30).  //cuenta contpaq
                            $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, clientes es un cargo = 0
                            $this->setEspacios( $this->numero($value->{'vacaciones'.$iper}) , 20).  //importe movimiento - retencion
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios("VACACIONES Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          if($value->{'prima_vacacional'.$iper} > 0)
            $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($this->getCuentaNPrimaVacacional(true, $iper),30).  //cuenta contpaq
                            $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, clientes es un cargo = 0
                            $this->setEspacios( $this->numero($value->{'prima_vacacional'.$iper}) , 20).  //importe movimiento - retencion
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios("PRIMA VACACIONAL Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          if($value->{'aguinaldo'.$iper} > 0)
            $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($this->getCuentaNAguinaldo(true, $iper),30).  //cuenta contpaq
                            $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, clientes es un cargo = 0
                            $this->setEspacios( $this->numero($value->{'aguinaldo'.$iper}) , 20).  //importe movimiento - retencion
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios("AGUINALDOS Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          if($value->{'horas_extras'.$iper} > 0)
            $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($this->getCuentaNHorasHex(true, $iper),30).  //cuenta contpaq
                            $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, clientes es un cargo = 0
                            $this->setEspacios( $this->numero($value->{'horas_extras'.$iper}) , 20).  //importe movimiento - retencion
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios("HRS EXTRAS Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          if($value->{'pasistencia'.$iper} > 0)
            $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($this->getPAsistenciaContpaq(true, $iper),30).  //cuenta contpaq
                            $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, clientes es un cargo = 0
                            $this->setEspacios( $this->numero($value->{'pasistencia'.$iper}) , 20).  //importe movimiento - retencion
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios("ASISTENCIA Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          if($value->{'indemnizaciones'.$iper} > 0)
            $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($this->getNIndemnizacionesContpaq(true, $iper),30).  //cuenta contpaq
                            $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, clientes es un cargo = 0
                            $this->setEspacios( $this->numero($value->{'indemnizaciones'.$iper}) , 20).  //importe movimiento - retencion
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios("ASISTENCIA Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
        }

        //Colocamos los abonos de la nomina
        if($value->total_neto > 0)
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($this->getCuentaNominaPagar(),30).  //cuenta contpaq
                          $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, abono = 1
                          $this->setEspacios( $this->numero($value->total_neto) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios("NOMINAS POR PAGAR Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                          $this->setEspacios('',4)."\r\n"; //segmento de negocio
        if(abs($value->subsidio) > 0)
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($this->getCuentaNSubsidio(),30).  //cuenta contpaq
                          $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, abono = 1
                          $this->setEspacios( $this->numero($value->subsidio*-1) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios("SUBSIDIO AL EMPLEO Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                          $this->setEspacios('',4)."\r\n"; //segmento de negocio
        if($value->imss > 0)
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($this->getCuentaNImss(),30).  //cuenta contpaq
                          $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, abono = 1
                          $this->setEspacios( $this->numero($value->imss) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios("IMSS RETENIDO Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                          $this->setEspacios('',4)."\r\n"; //segmento de negocio
        if($value->vejez > 0)
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($this->getCuentaNVejez(),30).  //cuenta contpaq
                          $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, abono = 1
                          $this->setEspacios( $this->numero($value->vejez) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios("RETIRO CENSATIA Y VEJEZ Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                          $this->setEspacios('',4)."\r\n"; //segmento de negocio
        if($value->infonavit > 0)
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($this->getCuentaNInfonavit(),30).  //cuenta contpaq
                          $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, abono = 1
                          $this->setEspacios( $this->numero($value->infonavit) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios("CREDITO INFONAVIT Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                          $this->setEspacios('',4)."\r\n"; //segmento de negocio
        if($value->isr > 0)
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($this->getCuentaNIsr(),30).  //cuenta contpaq
                          $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, abono = 1
                          $this->setEspacios( $this->numero($value->isr) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios("ISR Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                          $this->setEspacios('',4)."\r\n"; //segmento de negocio
        foreach ($prestamos as $keyp => $prestamo)
        {
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($prestamo->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, abono = 1
                          $this->setEspacios( $this->numero($prestamo->prestamo) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios("{$prestamo->nombre} Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                          $this->setEspacios('',4)."\r\n"; //segmento de negocio
        }

        foreach ($fondos_ahorro as $keyp => $fondo_ahorro)
        {
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($fondo_ahorro->fondo_ahorro_cpi,30).  //cuenta contpaq
                          $this->setEspacios("Nom {$value->semana}",10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, abono = 1
                          $this->setEspacios( $this->numero($fondo_ahorro->fondo_ahorro) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios("FA {$fondo_ahorro->nombre} Nom {$value->semana} Sem {$value->fecha_inicio}-{$value->fecha_final}", 100). //concepto
                          $this->setEspacios('',4)."\r\n"; //segmento de negocio
        }

        $folio++;
      }
    }

    $response['folio'] = $folio-1;
    $response['data'] = mb_strtoupper($response['data'], 'UTF-8');

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LOS PRODUCTOS
   * @return [type] [description]
   */
  public function polizaDiarioProducto()
  {
    $response = array('data' => '', 'facturas' => array(), 'folio' => '');
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(b.fecha_tara) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if (isset($_GET['ftipo2']{0}) && $_GET['ftipo2'] == 'pr') {
      $this->load->model('areas_model'); // aria poliza diario producto
      $_GET['ftipo22'] = $this->input->get('ftipo22') != '' ? $_GET['ftipo22'] : $this->areas_model->getAreaDefault();
      if ($this->input->get('ftipo22') != '')
        $sql .= " AND b.id_area = " . $_GET['ftipo22'];
    }

    $cuenta_cpi1 = '13000049';
    $cuenta_cpi2 = '43000010';

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND b.id_empresa = '".$_GET['fid_empresa']."'";

    $sql .= " AND b.tipo = 'en'";

    $fecha = $_GET['ffecha1'];
    if($_GET['ffecha1'] > $_GET['ffecha2'])
      $fecha = $_GET['ffecha2'];

    $folio = $this->input->get('ffolio');

    $query = $this->db->query(
        "SELECT Sum(b.kilos_neto) AS kilos,
            Sum(b.total_cajas) AS cajas,
            Sum(b.importe) AS importe,
            (CASE Sum(b.kilos_neto) WHEN 0 THEN (Sum(b.importe)/1) ELSE (Sum(b.importe)/Sum(b.kilos_neto)) END) AS precio,
            p.id_proveedor, p.nombre_fiscal AS proveedor, p.cuenta_cpi
         FROM bascula b
         JOIN ( SELECT bascula_compra.id_bascula, sum(bascula_compra.precio) / count(bascula_compra.id_calidad)::double precision AS precio
                 FROM bascula_compra
                GROUP BY bascula_compra.id_bascula) bc ON b.id_bascula = bc.id_bascula
         LEFT JOIN proveedores p ON p.id_proveedor = b.id_proveedor
        WHERE b.status = true
           {$sql}
        GROUP BY p.id_proveedor, p.nombre_fiscal, p.cuenta_cpi
        ORDER BY p.nombre_fiscal ASC
        "
      );

    if($query->num_rows() > 0)
    {
      $response['facturas'] = $query->result();

      $impuestos = array('iva_acreditar' => array('cuenta_cpi' => $this->getCuentaIvaXAcreditar(), 'importe' => 0, 'tipo' => '1'),
                           'iva_retenido' => array('cuenta_cpi' => $this->getCuentaIvaRetXPagar(), 'importe' => 0, 'tipo' => '0'),
                           'isr_retenido' => array('cuenta_cpi' => $this->getCuentaIsrRetXPagar(), 'importe' => 0, 'tipo' => '0'), );

      //Agregamos el header de la poliza
      $response['data'] .= $this->setEspacios('P',2).
                          $this->setEspacios(str_replace('-', '', $fecha),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                          $this->setEspacios($folio,9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios($this->input->get('fconcepto'),100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\r\n"; //ajuste
      $total_proveedores = 0;
      //Contenido de la Poliza
      foreach ($response['facturas'] as $key => $value)
      {
        //Colocamos el Cargo del proveedor
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios('PD'.$folio,10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, proveedor es un cargo = 1
                          $this->setEspacios( $this->numero($value->importe) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($this->input->get('fconcepto'), 100). //concepto
                          $this->setEspacios('',4)."\r\n"; //segmento de negocio
        $total_proveedores += $value->importe;
      }

      //Colocamos el abono del total
      $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                        $this->setEspacios($cuenta_cpi1,30).  //cuenta contpaq
                        $this->setEspacios('PD'.$folio,10).  //referencia movimiento
                        $this->setEspacios('0',1).  //tipo movimiento, proveedor es un abono = 0
                        $this->setEspacios( $this->numero($total_proveedores) , 20).  //importe movimiento - retencion
                        $this->setEspacios('0',10).  //iddiario poner 0
                        $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                        $this->setEspacios($this->input->get('fconcepto'), 100). //concepto
                        $this->setEspacios('',4)."\r\n"; //segmento de negocio
      $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                        $this->setEspacios($cuenta_cpi1,30).  //cuenta contpaq
                        $this->setEspacios('PD'.$folio,10).  //referencia movimiento
                        $this->setEspacios('1',1).  //tipo movimiento, proveedor es un abono = 0
                        $this->setEspacios( $this->numero($total_proveedores) , 20).  //importe movimiento - retencion
                        $this->setEspacios('0',10).  //iddiario poner 0
                        $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                        $this->setEspacios($this->input->get('fconcepto'), 100). //concepto
                        $this->setEspacios('',4)."\r\n"; //segmento de negocio
      $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                        $this->setEspacios($cuenta_cpi2,30).  //cuenta contpaq
                        $this->setEspacios('PD'.$folio,10).  //referencia movimiento
                        $this->setEspacios('0',1).  //tipo movimiento, proveedor es un abono = 0
                        $this->setEspacios( $this->numero($total_proveedores) , 20).  //importe movimiento - retencion
                        $this->setEspacios('0',10).  //iddiario poner 0
                        $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                        $this->setEspacios($this->input->get('fconcepto'), 100). //concepto
                        $this->setEspacios('',4)."\r\n"; //segmento de negocio
    }

    $response['folio'] = $folio;
    $response['data'] = mb_strtoupper($response['data'], 'UTF-8');

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE INGRESOS
   * @return [type] [description]
   */
  public function polizaIngreso()
  {
    $response = array('data' => '', 'abonos' => array(), 'folio' => '');
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(fa.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      $sql2 .= " AND Date(bm.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
    {
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";
      $sql2 .= " AND bc.id_empresa = '".$_GET['fid_empresa']."'";
    }
    $cuenta_cuadre = '';
    $query = $this->db->query(
      "SELECT *
      FROM (
        (
          SELECT
            bmf.id_movimiento, fa.ref_movimiento, fa.concepto, Sum(fa.total) AS total_abono,
            bc.cuenta_cpi, Sum(f.subtotal) AS subtotal, Sum(f.total) AS total, Sum(((fa.total*100/f.total)*f.importe_iva/100)) AS importe_iva,
            Sum(((fa.total*100/f.total)*f.retencion_iva/100)) AS retencion_iva, c.nombre_fiscal,
            c.cuenta_cpi AS cuenta_cpi_cliente, Date(fa.fecha) AS fecha, Sum(f.importe_iva) AS importe_ivat, Sum(f.retencion_iva) AS retencion_ivat,
            string_agg(f.id_factura::text || '-' || fa.id_abono::text, ',') AS idfacturas,
            'facturas'::character varying AS tipoo, 0::bigint AS es_traspaso
          FROM facturacion AS f
            INNER JOIN facturacion_abonos AS fa ON fa.id_factura = f.id_factura
            INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta
            INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
            INNER JOIN banco_movimientos_facturas AS bmf ON bmf.id_abono_factura = fa.id_abono
          WHERE f.status <> 'ca' AND f.status <> 'b' AND fa.poliza_ingreso = 'f'
             {$sql} AND ((f.fecha < '2014-01-01' AND f.is_factura = 'f') OR (f.is_factura = 't') )
             AND f.id_abono_factura IS NULL
          GROUP BY bmf.id_movimiento, fa.ref_movimiento, fa.concepto,
            bc.cuenta_cpi, c.nombre_fiscal, c.cuenta_cpi, Date(fa.fecha)
          ORDER BY bmf.id_movimiento ASC
        )
        UNION
        (
          SELECT
            bm.id_movimiento, bm.numero_ref AS ref_movimiento, bm.concepto, bm.monto AS total_abono,
            bc.cuenta_cpi, bm.monto AS subtotal, bm.monto AS total, 0 AS importe_iva, 0 AS retencion_iva,
            COALESCE(c.nombre_fiscal, cc.nombre, 'CUENTA CUADRE') AS nombre_fiscal,
            COALESCE(c.cuenta_cpi, bm.cuenta_cpi, '{$cuenta_cuadre}') AS cuenta_cpi_cliente, Date(bm.fecha) AS fecha,
            0 AS importe_ivat, 0 AS retencion_ivat, '' AS idfacturas,
            'banco'::character varying AS tipoo,
            (SELECT Count(id_movimiento) FROM banco_movimientos WHERE id_traspaso = bm.id_movimiento) AS es_traspaso
          FROM banco_movimientos AS bm
            INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = bm.id_cuenta
            LEFT JOIN clientes AS c ON c.id_cliente = bm.id_cliente
            LEFT JOIN banco_movimientos_facturas AS bmc ON bmc.id_movimiento = bm.id_movimiento
            LEFT JOIN cuentas_contpaq AS cc ON cc.cuenta = bm.cuenta_cpi
          WHERE bm.status = 't' AND bm.tipo = 't' AND bm.clasificacion <> 'elimon' {$sql2}
          GROUP BY bm.id_movimiento, bm.numero_ref, bm.concepto, bm.monto, bc.cuenta_cpi,
            bm.monto, c.nombre_fiscal, c.cuenta_cpi, bm.metodo_pago, Date(bm.fecha), bm.id_traspaso, cc.nombre
          HAVING Count(bmc.id_movimiento) = 0
          ORDER BY bm.fecha ASC
        )
      ) AS t
      WHERE es_traspaso = 0
      ORDER BY fecha ASC");

    if($query->num_rows() > 0)
    {
      $data = $query->result();
      $response['abonos'] = $data;

      $this->load->model('facturacion_model');

      $impuestos = array(
        'iva_trasladar'  => array('cuenta_cpi' => $this->getCuentaIvaXTrasladar(), 'importe' => 0, 'tipo' => '0'),
        'iva_trasladado' => array('cuenta_cpi' => $this->getCuentaIvaTrasladado(), 'importe' => 0, 'tipo' => '1'),
        'iva_retener'    => array('cuenta_cpi' => $this->getCuentaIvaRetXCobrarAc(), 'importe' => 0, 'tipo' => '0'),
        'iva_retenido'   => array('cuenta_cpi' => $this->getCuentaIvaRetCobradoAc(), 'importe' => 0, 'tipo' => '1'), );

      $folio = $this->input->get('ffolio');
      //Contenido de la Poliza
      foreach ($data as $key => $value)
      {
        //Son los pagos echos desde cuentas por cobrar
        if($value->tipoo == 'facturas')
        {
          //Agregamos el header de la poliza
          $response['data'] .= $this->setEspacios('P',2).
                              $this->setEspacios(str_replace('-', '', $value->fecha),8).$this->setEspacios('1',4,'r').  //tipo poliza = 1 poliza ingresos
                              $this->setEspacios($folio,9,'r').  //folio poliza
                              $this->setEspacios('1',1). //clase
                              $this->setEspacios('0',10). //iddiario
                              $this->setEspacios($value->concepto, 100). //concepto  'Ingresos, '.String::fechaATexto($value->fecha)
                              $this->setEspacios('11',2). //sistema de origen
                              $this->setEspacios('0',1). //impresa
                              $this->setEspacios('0',1)."\r\n"; //ajuste

          //Se obtiene un registro del abono si es que se pago de mas
          $query_mayor = $this->db->query(
          "SELECT Date(fao.fecha) AS fecha, fao.concepto, Sum(fao.total) AS total, fao.cuenta_cpi
          FROM facturacion_abonos AS fa
            INNER JOIN banco_movimientos_facturas AS bmf ON bmf.id_abono_factura = fa.id_abono
            INNER JOIN facturacion_abonos_otros AS fao ON fao.id_factura = fa.id_factura AND fao.id_abono = bmf.id_abono_factura
          WHERE fao.tipo = 'm' AND bmf.id_movimiento = {$value->id_movimiento}
          GROUP BY Date(fao.fecha), fao.concepto, fao.cuenta_cpi")->row();
          //Se obtiene un registro del abono si es que se pago de menos
          $query_saldar = $this->db->query(
          "SELECT Date(fao.fecha) AS fecha, fao.concepto, Sum(fao.total) AS total, fao.cuenta_cpi
          FROM facturacion_abonos AS fa
            INNER JOIN banco_movimientos_facturas AS bmf ON bmf.id_abono_factura = fa.id_abono
            INNER JOIN facturacion_abonos_otros AS fao ON fao.id_factura = fa.id_factura AND fao.id_abono = bmf.id_abono_factura
          WHERE fao.tipo = 's' AND bmf.id_movimiento = {$value->id_movimiento}
          GROUP BY Date(fao.fecha), fao.concepto, fao.cuenta_cpi")->row();

          $importe_iva = $value->importe_iva;
          $importe_retencion = $value->retencion_iva;
          // Quitamos la opcion que ponga todo el iva en el primer pago
          // $facturasIds = explode(',', $value->idfacturas);
          // foreach ($facturasIds as $keyi => $facid)
          // {
          //   $facid2 = explode('-', $facid);
          //   $infodac = $this->db->query("SELECT
          //       importe_iva, retencion_iva,
          //       (SELECT Count(*) FROM facturacion_abonos WHERE id_abono <= {$facid2[1]} AND id_factura = facturacion.id_factura) AS num
          //     FROM facturacion WHERE id_factura = {$facid2[0]}")->row();
          //   if($infodac->num == 1){
          //     $importe_iva += $infodac->importe_iva;
          //     $importe_retencion += $infodac->retencion_iva;
          //   }
          // }

          // $factor = $value->total_abono*100/($value->total); //abono*100/total_factura
          $impuestos['iva_retener']['importe']    = $importe_retencion; //$value->retencion_iva; //$factor*$value->retencion_iva/100;
          $impuestos['iva_retenido']['importe']   = $impuestos['iva_retener']['importe'];

          $impuestos['iva_trasladar']['importe']  = $importe_iva; //$value->importe_iva; //$factor*($value->importe_iva)/100;
          $impuestos['iva_trasladado']['importe'] = $impuestos['iva_trasladar']['importe'];
          $subtotal = $value->total_abono;//-$impuestos['iva_retener']['importe']-$impuestos['iva_trasladar']['importe'];

          //Colocamos el Cargo al Banco que se deposito el dinero
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                            $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, banco es un cargo = 0
                            $this->setEspacios( $this->numero($subtotal+( isset($query_mayor->cuenta_cpi)? $query_mayor->total: 0)-( isset($query_saldar->cuenta_cpi)? $query_saldar->total: 0) ) , 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($value->nombre_fiscal,100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          //Colocamos el Abono al Cliente que realizo el pago
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($value->cuenta_cpi_cliente,30).  //cuenta contpaq
                            $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                            $this->setEspacios('1',1).  //tipo movimiento, Cliente es un abono = 1
                            $this->setEspacios( $this->numero($subtotal), 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($value->concepto,100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          //Si hay abonos de mas se agregan a los mov
          if (isset($query_mayor->cuenta_cpi) && ((floor($query_mayor->total * 100) / 100) > 0))
            $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($query_mayor->cuenta_cpi,30).  //cuenta contpaq
                            $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                            $this->setEspacios('1',1).  //tipo movimiento, Cliente es un abono = 1
                            $this->setEspacios( $this->numero($query_mayor->total), 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($query_mayor->concepto,100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          //Si hay abonos de mas se agregan a los mov
          if (isset($query_saldar->cuenta_cpi) && ((floor($query_saldar->total * 100) / 100) > 0))
            $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($query_saldar->cuenta_cpi,30).  //cuenta contpaq
                            $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, Cliente es un abono = 1
                            $this->setEspacios( $this->numero($query_saldar->total), 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($query_saldar->concepto,100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio


          //Colocamos los impuestos de la factura
          foreach ($impuestos as $key => $impuesto)
          {
            if ($impuestos[$key]['importe'] > 0)
            {
              $response['data'] .= $this->setEspacios('M',2).
                            $this->setEspacios($impuesto['cuenta_cpi'],30).
                            $this->setEspacios($value->ref_movimiento,10).
                            $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                            $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                            $this->setEspacios('0',10).
                            $this->setEspacios('0.0',20).
                            $this->setEspacios($value->concepto,100).
                            $this->setEspacios('',4)."\r\n";
            }
          }
        }else //Son los depositos directos de banco
        {
          //Agregamos el header de la poliza
          $response['data'] .= $this->setEspacios('P',2).
                              $this->setEspacios(str_replace('-', '', $value->fecha),8).$this->setEspacios('1',4,'r').  //tipo poliza = 1 poliza ingresos
                              $this->setEspacios($folio,9,'r').  //folio poliza
                              $this->setEspacios('1',1). //clase
                              $this->setEspacios('0',10). //iddiario
                              $this->setEspacios($value->concepto, 100). //concepto
                              $this->setEspacios('11',2). //sistema de origen
                              $this->setEspacios('0',1). //impresa
                              $this->setEspacios('0',1)."\r\n"; //ajuste

          //Colocamos el Cargo al Banco que se deposito el dinero
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                            $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, banco es un cargo = 0
                            $this->setEspacios( $this->numero($value->total) , 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($value->nombre_fiscal,100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          //Colocamos el Abono al Cliente que realizo el pago
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($value->cuenta_cpi_cliente,30).  //cuenta contpaq
                            $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                            $this->setEspacios('1',1).  //tipo movimiento, Proveedor es un abono = 1
                            $this->setEspacios( $this->numero($value->total), 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($value->nombre_fiscal,100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
        }
        $folio++;
      }
      $response['folio'] = $folio-1;
    }

    $response['data'] = mb_strtoupper($response['data'], 'UTF-8');

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE EGRESO de limon
   * @return [type] [description]
   */
  public function polizaEgresoLimon()
  {
    $response = array('data' => '', 'abonos' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(fa.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $query = $this->db->query(
      "SELECT
        fa.id_pago, '' AS ref_movimiento, fa.concepto, fa.monto AS total_abono,
        bc.cuenta_cpi, Date(fa.fecha) AS fecha, p.nombre_fiscal, p.cuenta_cpi AS cuenta_cpi_prov
      FROM bascula_pagos AS fa
        INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta
        INNER JOIN bascula_pagos_basculas AS bpb ON bpb.id_pago = fa.id_pago
        INNER JOIN bascula AS f ON f.id_bascula = bpb.id_bascula
        INNER JOIN proveedores AS p ON p.id_proveedor = f.id_proveedor
      WHERE fa.status = 't' AND fa.poliza_egreso = 'f' AND fa.tipo_pago <> 'cheque'
         {$sql}
      GROUP BY fa.id_pago, fa.concepto, fa.monto, bc.cuenta_cpi, p.nombre_fiscal, p.cuenta_cpi
      ORDER BY fa.id_pago ASC
      ");

    if($query->num_rows() > 0)
    {
      $data = $query->result();
      $response['abonos'] = $data;
      $ffolio = $this->input->get('ffolio');

      $this->load->model('facturacion_model');

      $impuestos = array(
        'iva_acreditar'  => array('cuenta_cpi' => $this->getCuentaIvaXAcreditar(), 'importe' => 0, 'tipo' => '1'),
        'iva_acreditado' => array('cuenta_cpi' => $this->getCuentaIvaAcreditado(), 'importe' => 0, 'tipo' => '0'),
        'iva_retener'    => array('cuenta_cpi' => $this->getCuentaIvaRetXPagar(), 'importe' => 0, 'tipo' => '0'),
        'iva_retenido'   => array('cuenta_cpi' => $this->getCuentaIvaRetPagado(), 'importe' => 0, 'tipo' => '1'), );

      //Contenido de la Poliza
      foreach ($data as $key => $value)
      {
        //Agregamos el header de la poliza
        $response['data'] .= $this->setEspacios('P',2).
                            $this->setEspacios(str_replace('-', '', $value->fecha), 8).$this->setEspacios('2',4,'r').  //tipo poliza = 2 poliza egreso
                            $this->setEspacios($ffolio,9,'r').  //folio poliza
                            $this->setEspacios('1',1). //clase
                            $this->setEspacios('0',10). //iddiario
                            $this->setEspacios($value->nombre_fiscal,100). //concepto
                            $this->setEspacios('11',2). //sistema de origen
                            $this->setEspacios('0',1). //impresa
                            $this->setEspacios('0',1)."\r\n"; //ajuste

        // $data_frutas = $this->db->query(
        //     "SELECT b.id_bascula, b.importe, b.folio, p.id_proveedor, p.nombre_fiscal, p.cuenta_cpi
        //     FROM bascula AS b
        //       INNER JOIN bascula_pagos_basculas AS bpb ON bpb.id_bascula = b.id_bascula
        //       INNER JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor
        //     WHERE bpb.id_pago = {$value->id_pago}")->result();

        $subtotal = $value->total_abono;//-$impuestos['iva_retener']['importe']-$impuestos['iva_acreditar']['importe'];

        //Colocamos el Cargo al Proveedor que realizo el pago
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value->cuenta_cpi_prov,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento.'Fruta',10).  //referencia movimiento
                          $this->setEspacios('0',1).  //tipo movimiento, Proveedor es un cargo = 0
                          $this->setEspacios( $this->numero($subtotal), 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($value->concepto,100). //concepto
                          $this->setEspacios('',4)."\r\n"; //segmento de negocio
        //Colocamos el Abono al Banco que se deposito el dinero
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento.'Fruta',10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, banco es un abono = 1
                          $this->setEspacios( $this->numero($subtotal) , 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($value->nombre_fiscal,100). //concepto
                          $this->setEspacios('',4)."\r\n"; //segmento de negocio
        // //Colocamos el Abono al Proveedor que realizo el pago
        // foreach ($data_frutas as $key => $value_fruta)
        // {
        //   $response['data'] .= $this->setEspacios('M',2). //movimiento = M
        //                   $this->setEspacios($value_fruta->cuenta_cpi,30).  //cuenta contpaq
        //                   $this->setEspacios($value->ref_movimiento.'Fruta',10).  //referencia movimiento
        //                   $this->setEspacios('1',1).  //tipo movimiento, Proveedor es un abono = 1
        //                   $this->setEspacios( $this->numero($value_fruta->importe), 20).  //importe movimiento
        //                   $this->setEspacios('0',10).  //iddiario poner 0
        //                   $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
        //                   $this->setEspacios($value->concepto.' (Boleta:'.$value_fruta->folio.')',100). //concepto
        //                   $this->setEspacios('',4)."\r\n"; //segmento de negocio
        // }

        $ffolio++;
      }
    }

    $response['data'] = mb_strtoupper($response['data'], 'UTF-8');

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE EGRESO DE CHEQUES
   * @return [type] [description]
   */
  public function polizaEgreso($tipo_movimientos='cheque')
  {
    $response = array('data' => '', 'abonos' => array(), 'folio' => '');
    $sql = $sql2 = $sql_union_bascula = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(fa.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      $sql2 .= " AND Date(bm.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != ''){
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";
      $sql2 .= " AND bc.id_empresa = '".$_GET['fid_empresa']."'";
    }

    $cuenta_cuadre = $this->getCuentaCuadreGasto();

    if($tipo_movimientos == 'cheque'){
      $order_by = 't.fecha';
      $sql_union_bascula = "UNION
        (
          SELECT
            fa.id_pago AS id_movimiento, COALESCE(bm.numero_ref, '') AS ref_movimiento, fa.concepto, fa.monto AS total_abono, 0 AS retencion_isr,
            bc.cuenta_cpi, fa.monto AS subtotal, fa.monto AS total, 0 AS importe_iva,
            0 AS retencion_iva, 0 AS importe_ieps, p.nombre_fiscal, p.cuenta_cpi AS cuenta_cpi_proveedor,
            fa.tipo_pago AS metodo_pago, Date(fa.fecha) AS fecha, 0 AS es_compra, 0 AS es_traspaso,
            'limon'::character varying AS tipoo, 'f' AS desglosar_iva, '' as banco_cuenta_contpaq, 0 AS tcambio
          FROM bascula_pagos AS fa
            INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta
            INNER JOIN bascula_pagos_basculas AS bpb ON bpb.id_pago = fa.id_pago
            INNER JOIN bascula AS f ON f.id_bascula = bpb.id_bascula
            INNER JOIN proveedores AS p ON p.id_proveedor = f.id_proveedor
            LEFT JOIN banco_movimientos_bascula AS bmb ON bmb.id_bascula_pago = fa.id_pago
            LEFT JOIN banco_movimientos AS bm ON bm.id_movimiento = bmb.id_movimiento
          WHERE fa.status = 't' AND fa.poliza_egreso = 'f' AND fa.tipo_pago = 'cheque'
             {$sql}
          GROUP BY fa.id_pago, fa.concepto, fa.monto, bc.cuenta_cpi, p.nombre_fiscal, p.cuenta_cpi, bm.numero_ref
          ORDER BY fa.id_pago ASC
        )
        UNION
        (
          SELECT
            fa.id_pago AS id_movimiento, COALESCE(bm.numero_ref, '') AS ref_movimiento, 'CANCELADO' AS concepto, fa.monto AS total_abono, 0 AS retencion_isr,
            bc.cuenta_cpi, fa.monto AS subtotal, 0 AS total, 0 AS importe_iva,
            0 AS retencion_iva, 0 AS importe_ieps, COALESCE(p.nombre_fiscal, 'CUENTA CUADRE') AS nombre_fiscal, COALESCE(p.cuenta_cpi, '{$cuenta_cuadre}') AS cuenta_cpi_proveedor,
            fa.tipo_pago AS metodo_pago, Date(fa.fecha) AS fecha, 0 AS es_compra, 0 AS es_traspaso,
            'banco-chc'::character varying AS tipoo, 'f' AS desglosar_iva, '' as banco_cuenta_contpaq, 0 AS tcambio
          FROM bascula_pagos AS fa
            INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta
            INNER JOIN bascula_pagos_basculas AS bpb ON bpb.id_pago = fa.id_pago
            INNER JOIN bascula AS f ON f.id_bascula = bpb.id_bascula
            INNER JOIN proveedores AS p ON p.id_proveedor = f.id_proveedor
            LEFT JOIN banco_movimientos_bascula AS bmb ON bmb.id_bascula_pago = fa.id_pago
            LEFT JOIN banco_movimientos AS bm ON bm.id_movimiento = bmb.id_movimiento
          WHERE fa.status = 'f' AND fa.poliza_egreso = 'f' AND fa.tipo_pago = 'cheque'
             {$sql}
          GROUP BY fa.id_pago, fa.concepto, fa.monto, bc.cuenta_cpi, p.nombre_fiscal, p.cuenta_cpi, bm.numero_ref
          ORDER BY fa.id_pago ASC
        )
        UNION
        (
          SELECT
            bm.id_movimiento, bm.numero_ref AS ref_movimiento, 'CANCELADO' AS concepto, bm.monto AS total_abono, 0 AS retencion_isr,
            bc.cuenta_cpi, bm.monto AS subtotal, 0 AS total, 0 AS importe_iva, 0 AS retencion_iva, 0 AS importe_ieps,
            COALESCE(bc.alias, 'CUENTA CUADRE') AS nombre_fiscal,
            COALESCE(c.cuenta_cpi, '{$cuenta_cuadre}') AS cuenta_cpi_proveedor, bm.metodo_pago, Date(bm.fecha) AS fecha,
            Count(bmc.id_movimiento) AS es_compra, COALESCE(bm.id_traspaso, 0) AS es_traspaso, 'banco-chc'::character varying AS tipoo,
            bm.desglosar_iva, bm.cuenta_cpi as banco_cuenta_contpaq, 0 AS tcambio
          FROM banco_movimientos AS bm
            INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = bm.id_cuenta
            LEFT JOIN proveedores AS c ON c.id_proveedor = bm.id_proveedor
            LEFT JOIN banco_movimientos_compras AS bmc ON bmc.id_movimiento = bm.id_movimiento
            LEFT JOIN cuentas_contpaq AS cc ON cc.cuenta = bm.cuenta_cpi
          WHERE bm.status = 'f' AND bm.tipo = 'f' AND bm.clasificacion <> 'elimon'
            {$sql2} AND LOWER(bm.metodo_pago) = 'cheque'
          GROUP BY bm.id_movimiento, bm.numero_ref, bm.concepto, bm.monto, bc.cuenta_cpi,
            bm.monto, bc.alias, c.cuenta_cpi, bm.metodo_pago, Date(bm.fecha), bm.id_traspaso
          HAVING Count(bmc.id_movimiento) = 0
          ORDER BY bm.fecha ASC
        )";
      $sql .= " AND LOWER(bm.metodo_pago) = 'cheque' ";
      $sql2 .= " AND LOWER(bm.metodo_pago) = 'cheque' ";
    }else{
      $order_by = 't.fecha';
      $sql .= " AND LOWER(bm.metodo_pago) <> 'cheque' ";
      $sql2 .= " AND LOWER(bm.metodo_pago) <> 'cheque' ";
    }
    $order_by = 'ORDER BY t.fecha ASC, t.id_movimiento ASC';

    $query = $this->db->query(
      "SELECT *
      FROM
      (
        (
          SELECT
            bmc.id_movimiento, fa.ref_movimiento, fa.concepto, Sum(fa.total) AS total_abono, Sum(((fa.total*100/f.total)*f.retencion_isr/100)) AS retencion_isr,
            bc.cuenta_cpi, Sum(f.subtotal) AS subtotal, Sum(f.total) AS total, Sum(((fa.total*100/f.total)*f.importe_iva/100)) AS importe_iva,
            Sum(((fa.total*100/f.total)*f.retencion_iva/100)) AS retencion_iva, Sum(((fa.total*100/f.total)*f.importe_ieps/100)) AS importe_ieps, c.nombre_fiscal,
            c.cuenta_cpi AS cuenta_cpi_proveedor, bm.metodo_pago, Date(fa.fecha) AS fecha, 0 AS es_compra, 0 AS es_traspaso,
            'facturas'::character varying AS tipoo, 'f' AS desglosar_iva, '' as banco_cuenta_contpaq, bm.tcambio
          FROM compras AS f
            INNER JOIN compras_abonos AS fa ON fa.id_compra = f.id_compra
            INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta
            INNER JOIN proveedores AS c ON c.id_proveedor = f.id_proveedor
            INNER JOIN banco_movimientos_compras AS bmc ON bmc.id_compra_abono = fa.id_abono
            INNER JOIN banco_movimientos AS bm ON bm.id_movimiento = bmc.id_movimiento
          WHERE f.status <> 'ca' AND fa.poliza_egreso = 'f'
             {$sql}
          GROUP BY bmc.id_movimiento, fa.ref_movimiento, fa.concepto,
            bc.cuenta_cpi, c.nombre_fiscal, c.cuenta_cpi, bm.metodo_pago, Date(fa.fecha), bm.tcambio
          ORDER BY bmc.id_movimiento ASC
        )
        UNION
        (
          SELECT
            bm.id_movimiento, bm.numero_ref AS ref_movimiento, bm.concepto, bm.monto AS total_abono, 0 AS retencion_isr,
            bc.cuenta_cpi, bm.monto AS subtotal, bm.monto AS total, 0 AS importe_iva, 0 AS retencion_iva, 0 AS importe_ieps,
            COALESCE(c.nombre_fiscal, cc.nombre, 'CUENTA CUADRE') AS nombre_fiscal,
            COALESCE(c.cuenta_cpi, '{$cuenta_cuadre}') AS cuenta_cpi_proveedor, bm.metodo_pago, Date(bm.fecha) AS fecha,
            Count(bmc.id_movimiento) AS es_compra, COALESCE(bm.id_traspaso, 0) AS es_traspaso, 'banco'::character varying AS tipoo,
            bm.desglosar_iva, bm.cuenta_cpi as banco_cuenta_contpaq, 0 AS tcambio
          FROM banco_movimientos AS bm
            INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = bm.id_cuenta
            LEFT JOIN proveedores AS c ON c.id_proveedor = bm.id_proveedor
            LEFT JOIN banco_movimientos_compras AS bmc ON bmc.id_movimiento = bm.id_movimiento
            LEFT JOIN cuentas_contpaq AS cc ON cc.cuenta = bm.cuenta_cpi
          WHERE bm.status = 't' AND bm.tipo = 'f' AND bm.clasificacion <> 'elimon' {$sql2}
          GROUP BY bm.id_movimiento, bm.numero_ref, bm.concepto, bm.monto, bc.cuenta_cpi,
            bm.monto, c.nombre_fiscal, c.cuenta_cpi, bm.metodo_pago, Date(bm.fecha), bm.id_traspaso, cc.nombre
          HAVING Count(bmc.id_movimiento) = 0
          ORDER BY bm.fecha ASC
        )
        {$sql_union_bascula}
      ) AS t
      {$order_by}
      ");

    // $cuenta_cuadre = $this->getCuentaCuadreGasto();
    // $query2 = $this->db->query(
    //   "SELECT
    //     bm.id_movimiento, bm.numero_ref, bm.concepto, bm.monto AS total_abono,
    //     bc.cuenta_cpi, bm.monto AS subtotal, bm.monto AS total, COALESCE(c.nombre_fiscal, 'CUENTA CUADRE') AS nombre_fiscal,
    //     COALESCE(c.cuenta_cpi, '{$cuenta_cuadre}') AS cuenta_cpi_proveedor, bm.metodo_pago, Date(bm.fecha) AS fecha,
    //     Count(bmc.id_movimiento) AS es_compra, COALESCE(bm.id_traspaso, 0) AS es_traspaso
    //   FROM banco_movimientos AS bm
    //     INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = bm.id_cuenta
    //     LEFT JOIN proveedores AS c ON c.id_proveedor = bm.id_proveedor
    //     LEFT JOIN banco_movimientos_compras AS bmc ON bmc.id_movimiento = bm.id_movimiento
    //   WHERE bm.status = 't' AND bm.tipo = 'f' {$sql2}
    //   GROUP BY bm.id_movimiento, bm.numero_ref, bm.concepto, bm.monto, bc.cuenta_cpi,
    //     bm.monto, c.nombre_fiscal, c.cuenta_cpi, bm.metodo_pago, Date(bm.fecha), bm.id_traspaso
    //   HAVING Count(bmc.id_movimiento) = 0
    //   ORDER BY bm.fecha ASC
    //   ");

    if($query->num_rows() > 0) // || $query2->num_rows() > 0
    {
      $data = $query->result();
      $response['abonos'] = $data;
      // $response['banco_mov'] = $query2->result(); //movimientos directos del banco

      $this->load->model('facturacion_model');
      $this->load->model('banco_cuentas_model');

      $impuestos = array(
        'iva_acreditado' => array('cuenta_cpi' => $this->getCuentaIvaAcreditado(), 'importe' => 0, 'tipo' => '0'),
        'iva_acreditar'  => array('cuenta_cpi' => $this->getCuentaIvaXAcreditar(), 'importe' => 0, 'tipo' => '1'),
        'iva_retener'    => array('cuenta_cpi' => $this->getCuentaIvaRetXPagar(), 'importe' => 0, 'tipo' => '0'),
        'iva_retenido'   => array('cuenta_cpi' => $this->getCuentaIvaRetPagado(), 'importe' => 0, 'tipo' => '1'),
        'isr_retener'    => array('cuenta_cpi' => $this->getCuentaIsrRetXPagarHono(), 'importe' => 0, 'tipo' => '0'),
        'isr_retenido'   => array('cuenta_cpi' => $this->getCuentaIsrRetPagadoHono(), 'importe' => 0, 'tipo' => '1'),
        'ieps_acreditado'  => array('cuenta_cpi' => $this->getCuentaIvaRetXPagar(), 'importe' => 0, 'tipo' => '0'),
        'ieps_acreditar'   => array('cuenta_cpi' => $this->getCuentaIvaRetPagado(), 'importe' => 0, 'tipo' => '1'), );

      $folio = $this->input->get('ffolio');
      $aux_idmovimiento = 0;
      //Contenido de la Poliza de las facturas de compra
      foreach ($data as $key => $value)
      {
        if ($value->tipoo == 'facturas')
        {
          //Agregamos el header de la poliza
          if($aux_idmovimiento != $value->id_movimiento)
            $response['data'] .= $this->setEspacios('P',2).
                              $this->setEspacios(str_replace('-', '', $value->fecha),8).$this->setEspacios('2',4,'r').  //tipo poliza = 2 poliza egreso
                              $this->setEspacios($folio,9,'r').  //folio poliza
                              $this->setEspacios('1',1). //clase
                              $this->setEspacios('0',10). //iddiario
                              $this->setEspacios($value->concepto,100). //concepto
                              $this->setEspacios('11',2). //sistema de origen
                              $this->setEspacios('0',1). //impresa
                              $this->setEspacios('0',1)."\r\n"; //ajuste

          // $factor = $value->total_abono*100/($value->total); //abono*100/total_factura
          $subtotal_ope = $value->total_abono-$value->importe_iva+$value->retencion_iva+$value->retencion_isr;
          $ret_iva_pos = ($value->retencion_iva/$subtotal_ope)*100;

          $impuestos['iva_retener']['cuenta_cpi']    = $this->getCuentaIvaRetXPagar();
          $impuestos['iva_retenido']['cuenta_cpi']   = $this->getCuentaIvaRetPagado();
          if($ret_iva_pos > 15)
          { // Asigana las cuentas de retencion al 100%
            $impuestos['iva_retener']['cuenta_cpi']    = $this->getCuentaIvaRetXPagar100();
            $impuestos['iva_retenido']['cuenta_cpi']   = $this->getCuentaIvaRetPagado100();
          }elseif($ret_iva_pos > 4.5)
          { // Asigana las cuentas de honorarios
            $impuestos['iva_retener']['cuenta_cpi']    = $this->getCuentaIvaRetXPagarHono();
            $impuestos['iva_retenido']['cuenta_cpi']   = $this->getCuentaIvaRetPagadoHono();
          }
          $impuestos['iva_retener']['importe']    = $value->retencion_iva; //$factor*$value->retencion_iva/100;
          $impuestos['iva_retenido']['importe']   = $impuestos['iva_retener']['importe'];

          $impuestos['isr_retener']['importe']    = $value->retencion_isr; //
          $impuestos['isr_retenido']['importe']   = $impuestos['isr_retener']['importe'];

          $impuestos['iva_acreditar']['importe']  = $value->importe_iva; //$factor*($value->importe_iva)/100;
          $impuestos['iva_acreditado']['importe'] = $impuestos['iva_acreditar']['importe'];

          $impuestos['ieps_acreditar']['importe']  = $value->importe_ieps; //$factor*($value->importe_iva)/100;
          $impuestos['ieps_acreditado']['importe'] = $impuestos['ieps_acreditar']['importe'];

          $subtotal = $subtotal2 = $value->total_abono;//-$impuestos['iva_retener']['importe']-$impuestos['iva_acreditar']['importe'];

          if ($value->tcambio > 0) {
            // obtiene el nuevo total de la compra de acuerdo al tipo de cambio actual
            $productoss = $this->db->query("SELECT
                  cp.cantidad, cp.precio_unitario, cp.porcentaje_iva, cp.porcentaje_retencion,
                  cp.porcentaje_ieps, cp.tipo_cambio, bm.monto
                FROM compras_abonos AS fa
                  INNER JOIN banco_movimientos_compras AS bmc ON bmc.id_compra_abono = fa.id_abono
                  INNER JOIN banco_movimientos AS bm ON bm.id_movimiento = bmc.id_movimiento
                  INNER JOIN compras_productos cp ON cp.id_compra = fa.id_compra
                WHERE bm.id_movimiento = {$value->id_movimiento}
                ORDER BY bmc.id_movimiento ASC")->result();
            $new_iva = $new_total = 0;
            if (count($productoss) > 0) {
              foreach ($productoss as $ppkey => $ppvalue) {
                $pu = $ppvalue->precio_unitario;
                if($ppvalue->tipo_cambio > 0 && $value->tcambio > 0) {
                  $pu = ($ppvalue->precio_unitario/$ppvalue->tipo_cambio)*$value->tcambio;
                }
                $subtotall  = $ppvalue->cantidad*$pu;
                $new_iva   += ($subtotall*$ppvalue->porcentaje_iva/100);
                $new_total += floor( ($subtotall+($subtotall*$ppvalue->porcentaje_iva/100)+($subtotall*$ppvalue->porcentaje_ieps/100)) * 100)/100;
              }

              $impuestos['iva_acreditar']['importe']  = ($value->total_abono*100/$new_total)*$new_iva/100;
              $subtotal2 = $productoss[0]->monto;
            }
          }

          //Colocamos el Cargo al Proveedor que realizo el pago
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($value->cuenta_cpi_proveedor,30).  //cuenta contpaq
                            $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, Proveedor es un cargo = 0
                            $this->setEspacios( $this->numero($subtotal), 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($value->nombre_fiscal,100). //concepto  $value->concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          //Colocamos el Abono al Banco que se deposito el dinero
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                            $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                            $this->setEspacios('1',1).  //tipo movimiento, banco es un abono = 1
                            $this->setEspacios( $this->numero($subtotal2) , 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($value->nombre_fiscal,100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio

          //Colocamos los impuestos de la factura
          foreach ($impuestos as $key => $impuesto)
          {
            if ($impuestos[$key]['importe'] > 0)
            {
              $response['data'] .= $this->setEspacios('M',2).
                            $this->setEspacios($impuesto['cuenta_cpi'],30).
                            $this->setEspacios($value->ref_movimiento,10).
                            $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                            $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                            $this->setEspacios('0',10).
                            $this->setEspacios('0.0',20).
                            $this->setEspacios($value->nombre_fiscal,100).  // $value->concepto
                            $this->setEspacios('',4)."\r\n";
            }
          }
        }else if($value->tipoo == 'banco'){ //Contenido de la Poliza de los movimientos directos de banco
          $total_retiro_banco = NULL;
          //Es traspaso entre cuentas bancarias, se cambian los numeros
          if($value->es_traspaso > 0)
          {
            $info_mov                    = $this->banco_cuentas_model->getMovimientoInfo($value->es_traspaso, true)['info'];
            $info_cuenta                 = $this->banco_cuentas_model->getCuentaInfo($info_mov->id_cuenta)['info'];
            $value->cuenta_cpi_proveedor = $info_cuenta->cuenta_cpi;
            $value->nombre_fiscal        = $info_cuenta->alias;
          }

          $impuestos2 = array('iva_activo' => array('cuenta_cpi' => $this->getCuentaIvaAcreditado(), 'importe' => 0, 'tipo' => '0'),);
          if ($value->desglosar_iva == 't')
          {
            $impuestos2['iva_activo']['importe'] = $value->total-($value->total/1.16);
            $total_retiro_banco = $value->total;
            // if($value->cuenta_cpi_proveedor == $value->banco_cuenta_contpaq ||
            //   ($value->cuenta_cpi_proveedor != $value->banco_cuenta_contpaq && $value->cuenta_cpi_proveedor != $cuenta_cuadre && $value->banco_cuenta_contpaq != ''))
              $value->total -= $impuestos2['iva_activo']['importe'];
          }

          //Agregamos el header de la poliza
          $response['data'] .= $this->setEspacios('P',2).
                              $this->setEspacios(str_replace('-', '', $value->fecha),8).$this->setEspacios('2',4,'r').  //tipo poliza = 2 poliza egreso
                              $this->setEspacios($folio,9,'r').  //folio poliza
                              $this->setEspacios('1',1). //clase
                              $this->setEspacios('0',10). //iddiario
                              $this->setEspacios($value->concepto, 100). //concepto
                              $this->setEspacios('11',2). //sistema de origen
                              $this->setEspacios('0',1). //impresa
                              $this->setEspacios('0',1)."\r\n"; //ajuste

          if ($value->cuenta_cpi_proveedor != '' && $value->cuenta_cpi_proveedor != $cuenta_cuadre && $value->es_traspaso == 0) { //&& $value->banco_cuenta_contpaq != ''
            // Pago a proveedores y sin cuenta seleccionada
            //Colocamos el Cargo al Proveedor que realizo el pago
            $response['data'] .= $this->setEspacios('M',2). //movimiento = hw_Modifyobject(connection, object_to_change, remove, add)
                              $this->setEspacios($value->cuenta_cpi_proveedor,30).  //cuenta contpaq
                              $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                              $this->setEspacios('0',1).  //tipo movimiento, Proveedor es un cargo = 0
                              $this->setEspacios( $this->numero((isset($total_retiro_banco)? $total_retiro_banco: $value->total)), 20).  //importe movimiento
                              $this->setEspacios('0',10).  //iddiario poner 0
                              $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                              $this->setEspacios($value->nombre_fiscal,100). //concepto
                              $this->setEspacios('',4)."\r\n"; //segmento de negocio

            if($value->banco_cuenta_contpaq != '') {
              //Colocamos el Cargo a la cuenta Compaq que realizo el pago
              $response['data'] .= $this->setEspacios('M',2). //movimiento = hw_Modifyobject(connection, object_to_change, remove, add)
                                $this->setEspacios($value->banco_cuenta_contpaq,30).  //cuenta contpaq
                                $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                                $this->setEspacios('0',1).  //tipo movimiento, Cuenta del mov es un cargo
                                $this->setEspacios( $this->numero($value->total), 20).  //importe movimiento
                                $this->setEspacios('0',10).  //iddiario poner 0
                                $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                                $this->setEspacios($value->nombre_fiscal,100). //concepto
                                $this->setEspacios('',4)."\r\n"; //segmento de negocio

              //Colocamos el Abono al Proveedor que realizo el pago
              $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                                $this->setEspacios($value->cuenta_cpi_proveedor,30).  //cuenta contpaq
                                $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                                $this->setEspacios('1',1).  //tipo movimiento, banco es un abono = 1
                                $this->setEspacios( $this->numero((isset($total_retiro_banco)? $total_retiro_banco: $value->total)) , 20).  //importe movimiento
                                $this->setEspacios('0',10).  //iddiario poner 0
                                $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                                $this->setEspacios($value->nombre_fiscal,100). //concepto
                                $this->setEspacios('',4)."\r\n"; //segmento de negocio
            }
          } elseif($value->cuenta_cpi_proveedor == $cuenta_cuadre && $value->banco_cuenta_contpaq != '' && $value->es_traspaso == 0) {
            // Pago de comisiones y Nomina, prestamos (cargo y abono)
            //Colocamos el Cargo a la cuenta que se selecciono
            $response['data'] .= $this->setEspacios('M',2). //movimiento = hw_Modifyobject(connection, object_to_change, remove, add)
                              $this->setEspacios($value->banco_cuenta_contpaq,30).  //cuenta contpaq cuenta_cpi_proveedor
                              $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                              $this->setEspacios('0',1).  //tipo movimiento, Proveedor es un cargo = 0
                              $this->setEspacios( $this->numero($value->total), 20).  //importe movimiento
                              $this->setEspacios('0',10).  //iddiario poner 0
                              $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                              $this->setEspacios($value->nombre_fiscal,100). //concepto
                              $this->setEspacios('',4)."\r\n"; //segmento de negocio
          } elseif($value->es_traspaso > 0) {
            // Traspaso de dinero
            //Colocamos el Cargo a la cuenta que se selecciono
            $response['data'] .= $this->setEspacios('M',2). //movimiento = hw_Modifyobject(connection, object_to_change, remove, add)
                              $this->setEspacios($value->cuenta_cpi_proveedor,30).  //cuenta contpaq cuenta_cpi_proveedor
                              $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                              $this->setEspacios('0',1).  //tipo movimiento, Proveedor es un cargo = 0
                              $this->setEspacios( $this->numero($value->total), 20).  //importe movimiento
                              $this->setEspacios('0',10).  //iddiario poner 0
                              $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                              $this->setEspacios($value->nombre_fiscal,100). //concepto
                              $this->setEspacios('',4)."\r\n"; //segmento de negocio
          }



          // $es_proveedor = false;
          // $lock1 = false;
          // if($value->cuenta_cpi_proveedor != $value->banco_cuenta_contpaq)
          // {
          //   $es_proveedor = true;
          //   $lock1 = true;
          //   //Colocamos el Cargo al Proveedor que realizo el pago
          //   $response['data'] .= $this->setEspacios('M',2). //movimiento = hw_Modifyobject(connection, object_to_change, remove, add)
          //                     $this->setEspacios($value->cuenta_cpi_proveedor,30).  //cuenta contpaq
          //                     $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
          //                     $this->setEspacios('0',1).  //tipo movimiento, Proveedor es un cargo = 0
          //                     $this->setEspacios( $this->numero($value->total), 20).  //importe movimiento
          //                     $this->setEspacios('0',10).  //iddiario poner 0
          //                     $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
          //                     $this->setEspacios($value->nombre_fiscal,100). //concepto
          //                     $this->setEspacios('',4)."\r\n"; //segmento de negocio

          //   // Si se seleccionan las 2 cuentas y no es de cuadre, asigna cuentas
          //   if($value->cuenta_cpi_proveedor != $cuenta_cuadre && $value->banco_cuenta_contpaq != '')
          //   {
          //     //Colocamos el Cargo a la cuenta Compaq que realizo el pago
          //     $response['data'] .= $this->setEspacios('M',2). //movimiento = hw_Modifyobject(connection, object_to_change, remove, add)
          //                       $this->setEspacios($value->banco_cuenta_contpaq,30).  //cuenta contpaq
          //                       $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
          //                       $this->setEspacios('0',1).  //tipo movimiento, Cuenta del mov es un cargo
          //                       $this->setEspacios( $this->numero((isset($total_retiro_banco)? $total_retiro_banco: $value->total)), 20).  //importe movimiento
          //                       $this->setEspacios('0',10).  //iddiario poner 0
          //                       $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
          //                       $this->setEspacios($value->nombre_fiscal,100). //concepto
          //                       $this->setEspacios('',4)."\r\n"; //segmento de negocio
          //     //Colocamos el Abono al Proveedor que realizo el pago
          //     $response['data'] .= $this->setEspacios('M',2). //movimiento = M
          //                       $this->setEspacios($value->cuenta_cpi_proveedor,30).  //cuenta contpaq
          //                       $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
          //                       $this->setEspacios('1',1).  //tipo movimiento, banco es un abono = 1
          //                       $this->setEspacios( $this->numero((isset($total_retiro_banco)? $total_retiro_banco: $value->total)) , 20).  //importe movimiento
          //                       $this->setEspacios('0',10).  //iddiario poner 0
          //                       $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
          //                       $this->setEspacios($value->nombre_fiscal,100). //concepto
          //                       $this->setEspacios('',4)."\r\n"; //segmento de negocio
          //   }
          // }elseif ($value->banco_cuenta_contpaq != '' && $value->desglosar_iva == 't')
          // {
          //   //Colocamos el Cargo al Proveedor que realizo el pago
          //   $response['data'] .= $this->setEspacios('M',2). //movimiento = hw_Modifyobject(connection, object_to_change, remove, add)
          //                     $this->setEspacios($value->banco_cuenta_contpaq,30).  //cuenta contpaq cuenta_cpi_proveedor
          //                     $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
          //                     $this->setEspacios('0',1).  //tipo movimiento, Proveedor es un cargo = 0
          //                     $this->setEspacios( $this->numero($value->total), 20).  //importe movimiento
          //                     $this->setEspacios('0',10).  //iddiario poner 0
          //                     $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
          //                     $this->setEspacios($value->nombre_fiscal,100). //concepto
          //                     $this->setEspacios('',4)."\r\n"; //segmento de negocio
          //   $lock1 = false;
          // }

          // if ($value->banco_cuenta_contpaq != '' && $value->desglosar_iva == 't' && $es_proveedor)
          // {
          //   // // Cuadra el Iva si es un proveedor y seleccionaron la cuenta de conpaq directo
          //   // $response['data'] .= $this->setEspacios('M',2). //movimiento = hw_Modifyobject(connection, object_to_change, remove, add)
          //   //                   $this->setEspacios($value->banco_cuenta_contpaq,30).  //cuenta contpaq cuenta_cpi_proveedor
          //   //                   $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
          //   //                   $this->setEspacios('1',1).  //tipo movimiento, Cuadra el IVa es un abono = 1
          //   //                   $this->setEspacios( $this->numero($impuestos2['iva_activo']['importe']), 20).  //importe movimiento
          //   //                   $this->setEspacios('0',10).  //iddiario poner 0
          //   //                   $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
          //   //                   $this->setEspacios($value->nombre_fiscal,100). //concepto
          //   //                   $this->setEspacios('',4)."\r\n"; //segmento de negocio
          // }elseif($value->banco_cuenta_contpaq != '' && $value->banco_cuenta_contpaq == $value->cuenta_cpi_proveedor && $lock1)
          // {
          //   //Colocamos el Cargo al Proveedor que realizo el pago
          //   $response['data'] .= $this->setEspacios('M',2). //movimiento = hw_Modifyobject(connection, object_to_change, remove, add)
          //                     $this->setEspacios($value->banco_cuenta_contpaq,30).  //cuenta contpaq cuenta_cpi_proveedor
          //                     $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
          //                     $this->setEspacios('0',1).  //tipo movimiento, Proveedor es un cargo = 0
          //                     $this->setEspacios( $this->numero($value->total), 20).  //importe movimiento
          //                     $this->setEspacios('0',10).  //iddiario poner 0
          //                     $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
          //                     $this->setEspacios($value->nombre_fiscal,100). //concepto
          //                     $this->setEspacios('',4)."\r\n"; //segmento de negocio
          // }

          //Colocamos el Abono al Banco que se deposito el dinero
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                            $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                            $this->setEspacios('1',1).  //tipo movimiento, banco es un abono = 1
                            $this->setEspacios( $this->numero((isset($total_retiro_banco)? $total_retiro_banco: $value->total)) , 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($value->nombre_fiscal,100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          //Colocamos los impuestos de la factura
          foreach ($impuestos2 as $key => $impuesto)
          {
            if ($impuestos2[$key]['importe'] > 0)
            {
              $response['data'] .= $this->setEspacios('M',2).
                            $this->setEspacios($impuesto['cuenta_cpi'],30).
                            $this->setEspacios($value->ref_movimiento,10).
                            $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                            $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                            $this->setEspacios('0',10).
                            $this->setEspacios('0.0',20).
                            $this->setEspacios($value->nombre_fiscal,100).  // $value->concepto
                            $this->setEspacios('',4)."\r\n";
            }
          }
        }elseif($value->tipoo == 'banco-chc') {
          //Agregamos el header de la poliza
          $response['data'] .= $this->setEspacios('P',2).
                              $this->setEspacios(str_replace('-', '', $value->fecha),8).$this->setEspacios('2',4,'r').  //tipo poliza = 2 poliza egreso
                              $this->setEspacios($folio,9,'r').  //folio poliza
                              $this->setEspacios('1',1). //clase
                              $this->setEspacios('0',10). //iddiario
                              $this->setEspacios($value->concepto, 100). //concepto
                              $this->setEspacios('11',2). //sistema de origen
                              $this->setEspacios('0',1). //impresa
                              $this->setEspacios('0',1)."\r\n"; //ajuste
          //Colocamos el Abono al Banco que se deposito el dinero
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                            $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                            $this->setEspacios('1',1).  //tipo movimiento, banco es un abono = 1
                            $this->setEspacios( $this->numero($value->total) , 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($value->nombre_fiscal,100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
        }else  // Poliza de limon de cheques
        {
          //Agregamos el header de la poliza
          $response['data'] .= $this->setEspacios('P',2).
                              $this->setEspacios(str_replace('-', '', $value->fecha), 8).$this->setEspacios('2',4,'r').  //tipo poliza = 2 poliza egreso
                              $this->setEspacios($folio,9,'r').  //folio poliza
                              $this->setEspacios('1',1). //clase
                              $this->setEspacios('0',10). //iddiario
                              $this->setEspacios($value->nombre_fiscal,100). //concepto
                              $this->setEspacios('11',2). //sistema de origen
                              $this->setEspacios('0',1). //impresa
                              $this->setEspacios('0',1)."\r\n"; //ajuste

          $subtotal = $value->total_abono;//-$impuestos['iva_retener']['importe']-$impuestos['iva_acreditar']['importe'];

          //Colocamos el Cargo al Proveedor que realizo el pago
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($value->cuenta_cpi_proveedor,30).  //cuenta contpaq
                            $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                            $this->setEspacios('0',1).  //tipo movimiento, Proveedor es un cargo = 0
                            $this->setEspacios($this->numero($subtotal), 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($value->concepto,100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
          //Colocamos el Abono al Banco que se deposito el dinero
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                            $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                            $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                            $this->setEspacios('1',1).  //tipo movimiento, banco es un abono = 1
                            $this->setEspacios($this->numero($subtotal) , 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios($value->nombre_fiscal,100). //concepto
                            $this->setEspacios('',4)."\r\n"; //segmento de negocio
        }

        if($aux_idmovimiento != $value->id_movimiento)
        {
          $aux_idmovimiento = $value->id_movimiento;
          $folio++;
        }
      }

      // exit;

      // //Contenido de la Poliza de los movimientos directos de banco
      // foreach ($response['banco_mov'] as $key => $value)
      // {
      //   //Es traspaso entre cuentas bancarias, se cambian los numeros
      //   if($value->es_traspaso > 0)
      //   {
      //     $info_mov                    = $this->banco_cuentas_model->getMovimientoInfo($value->es_traspaso, true)['info'];
      //     $info_cuenta                 = $this->banco_cuentas_model->getCuentaInfo($info_mov->id_cuenta)['info'];
      //     $value->cuenta_cpi_proveedor = $info_cuenta->cuenta_cpi;
      //     $value->nombre_fiscal        = $info_cuenta->alias;
      //   }

      //   //Agregamos el header de la poliza
      //   $response['data'] .= $this->setEspacios('P',2).
      //                       $this->setEspacios(str_replace('-', '', $value->fecha),8).$this->setEspacios('2',4,'r').  //tipo poliza = 2 poliza egreso
      //                       $this->setEspacios($folio,9,'r').  //folio poliza
      //                       $this->setEspacios('1',1). //clase
      //                       $this->setEspacios('0',10). //iddiario
      //                       $this->setEspacios('Egresos de gastos, '.String::fechaATexto($value->fecha),100). //concepto
      //                       $this->setEspacios('11',2). //sistema de origen
      //                       $this->setEspacios('0',1). //impresa
      //                       $this->setEspacios('0',1)."\r\n"; //ajuste

      //   //Colocamos el Cargo al Banco que se deposito el dinero
      //   $response['data'] .= $this->setEspacios('M',2). //movimiento = M
      //                     $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
      //                     $this->setEspacios($value->numero_ref,10).  //referencia movimiento
      //                     $this->setEspacios('0',1).  //tipo movimiento, banco es un cargo = 0
      //                     $this->setEspacios( $this->numero($value->total) , 20).  //importe movimiento
      //                     $this->setEspacios('0',10).  //iddiario poner 0
      //                     $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
      //                     $this->setEspacios($value->nombre_fiscal,100). //concepto
      //                     $this->setEspacios('',4)."\r\n"; //segmento de negocio
      //   //Colocamos el Abono al Proveedor que realizo el pago
      //   $response['data'] .= $this->setEspacios('M',2). //movimiento = M
      //                     $this->setEspacios($value->cuenta_cpi_proveedor,30).  //cuenta contpaq
      //                     $this->setEspacios($value->numero_ref,10).  //referencia movimiento
      //                     $this->setEspacios('1',1).  //tipo movimiento, Proveedor es un abono = 1
      //                     $this->setEspacios( $this->numero($value->total), 20).  //importe movimiento
      //                     $this->setEspacios('0',10).  //iddiario poner 0
      //                     $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
      //                     $this->setEspacios($value->nombre_fiscal,100). //concepto
      //                     $this->setEspacios('',4)."\r\n"; //segmento de negocio
      //   $folio++;
      // }

      $response['folio'] = $folio-1;
    }

    $response['data'] = mb_strtoupper($response['data'], 'UTF-8');

    return $response;
  }



   /**
    * Visualiza/Descarga el PDF para el Reporte Diario de Entradas.
    *
    * @return void
    */
  public function generaPoliza()
  {
    $this->empresaId = $this->input->get('fid_empresa');
    $response = array('data' => '', 'facturas' => array());
    if ($this->input->get('ftipo') == '3') //******Polizas Diario
    {
      if($this->input->get('ftipo2') == 'v') //**Diario de ventas
      {
        $response = $this->polizaDiarioVentas();

        //actualizamos el estado de la factura y descarga el archivo
        if (isset($_POST['poliza']{0}))
        {
          $idsf = array();
          foreach ($response['facturas'] as $key => $value)
            $idsf[] = $value->id_factura;
          if(count($idsf) > 0)
          {
            // $this->db->where_in('id_factura', $idsf);
            // $this->db->update('facturacion', array('poliza_diario' => 't'));

            $_GET['poliza_nombre'] = 'polizadiario '.String::fechaATexto($_GET['ffecha1']).'.txt';
            file_put_contents(APPPATH.'media/polizas/'.$_GET['poliza_nombre'], $response['data']);
            $this->addPoliza($response['data'], $response['folio']); //se registra la poliza en la BD
          }
        }
      }elseif($this->input->get('ftipo2') == 'vnc') //**Diario de notas de credito de Ventas
      {
        $response = $this->polizaDiarioVentasNC();

        //actualizamos el estado de la factura y descarga el archivo
        if (isset($_POST['poliza']{0}))
        {
          $idsf = array();
          foreach ($response['facturas'] as $key => $value)
            $idsf[] = $value->id_factura;
          if(count($idsf) > 0)
          {
            // $this->db->where_in('id_factura', $idsf);
            // $this->db->update('facturacion', array('poliza_diario' => 't'));

            $_GET['poliza_nombre'] = 'polizadiarionc '.String::fechaATexto($_GET['ffecha1']).'.txt';
            file_put_contents(APPPATH.'media/polizas/'.$_GET['poliza_nombre'], $response['data']);
            $this->addPoliza($response['data'], $response['folio']); //se registra la poliza en la BD
          }
        }
      }elseif($this->input->get('ftipo2') == 'g')
      {
        $response = $this->polizaDiarioGastos();

        //actualizamos el estado de la factura y bascula y descarga el archivo
        if (isset($_POST['poliza']{0}))
        {
          $idsf = array();
          foreach ($response['facturas'] as $key => $value)
            $idsf[] = $value->id_compra;
          $idsb = array();
          // foreach ($response['bascula'] as $key => $value)
          //   $idsb[] = $value->id_bascula;
          if(count($idsf) > 0 || count($idsb) > 0)
          {
            if(count($idsf) > 0)
            {
              // $this->db->where_in('id_compra', $idsf);
              // $this->db->update('compras', array('poliza_diario' => 't'));
            }
            if(count($idsb) > 0)
            {
              // $this->db->where_in('id_bascula', $idsb);
              // $this->db->update('bascula', array('poliza_diario' => 't'));
            }


            $_GET['poliza_nombre'] = 'polizadiarionc '.String::fechaATexto($_GET['ffecha1']).'.txt';
            file_put_contents(APPPATH.'media/polizas/'.$_GET['poliza_nombre'], $response['data']);
            $this->addPoliza($response['data'], $response['folio']); //se registra la poliza en la BD
          }
        }
      }elseif($this->input->get('ftipo2') == 'gnc') //**Diario de notas de credito de Gastos
      {
        $response = $this->polizaDiarioGastosNC();

        //actualizamos el estado de la factura y descarga el archivo
        if (isset($_POST['poliza']{0}))
        {
          $idsf = array();
          // foreach ($response['facturas'] as $key => $value)
          //   $idsf[] = $value->id_factura;
          // if(count($idsf) > 0)
          // {
            // $this->db->where_in('id_factura', $idsf);
            // $this->db->update('facturacion', array('poliza_diario' => 't'));

            $_GET['poliza_nombre'] = 'polizadiarioncg '.String::fechaATexto($_GET['ffecha1']).'.txt';
            file_put_contents(APPPATH.'media/polizas/'.$_GET['poliza_nombre'], $response['data']);
            $this->addPoliza($response['data'], $response['folio']); //se registra la poliza en la BD
          // }
        }
      }elseif($this->input->get('ftipo2') == 'no') //nomina diario
      {
        $response = $this->polizaDiarioNomina();

        //actualizamos el estado de la factura y bascula y descarga el archivo
        if (isset($_POST['poliza']{0}))
        {
          $idsf = array();
          // foreach ($response['facturas'] as $key => $value)
          //   $idsf[] = $value->id_compra;
          $idsb = array();
          // foreach ($response['bascula'] as $key => $value)
          //   $idsb[] = $value->id_bascula;
          // if(count($idsf) > 0 || count($idsb) > 0)
          // {
          //   if(count($idsf) > 0)
          //   {
          //     // $this->db->where_in('id_compra', $idsf);
          //     // $this->db->update('compras', array('poliza_diario' => 't'));
          //   }
          //   if(count($idsb) > 0)
          //   {
          //     // $this->db->where_in('id_bascula', $idsb);
          //     // $this->db->update('bascula', array('poliza_diario' => 't'));
          //   }


            $_GET['poliza_nombre'] = 'polizadiarionc '.String::fechaATexto($_GET['ffecha1']).'.txt';
            file_put_contents(APPPATH.'media/polizas/'.$_GET['poliza_nombre'], $response['data']);
            $this->addPoliza($response['data'], $response['folio']); //se registra la poliza en la BD
          // }
        }
      }elseif($this->input->get('ftipo2') == 'pr') //producto
      {
        $area = $this->db->query("SELECT id_area, nombre
            FROM areas WHERE id_area = ".$this->input->get('ftipo22'))->row();
        $response = $this->polizaDiarioProducto();

        //actualizamos el estado de la factura y bascula y descarga el archivo
        if (isset($_POST['poliza']{0}))
        {
            $_GET['poliza_nombre'] = 'polizadiariopr '.String::fechaATexto($_GET['ffecha1']).'.txt';
            file_put_contents(APPPATH.'media/polizas/'.$_GET['poliza_nombre'], $response['data']);
            $this->addPoliza($response['data'], $response['folio']); //se registra la poliza en la BD
          // }
        }
      }


    }elseif ($this->input->get('ftipo') == '1')  //*******Polizas Ingresos
    {
      $response = $this->polizaIngreso();
      //actualizamos el estado de los abonos de las facturas
      if (isset($_POST['poliza']{0}))
      {
        $idsa = array();
        foreach ($response['abonos'] as $key => $value)
          $idsa[] = $value->id_movimiento;
        if(count($idsa) > 0)
        {
          // $this->db->where_in('id_abono', $idsa);
          // $this->db->update('facturacion_abonos', array('poliza_ingreso' => 't'));

          $_GET['poliza_nombre'] = 'polizaingreso '.String::fechaATexto($_GET['ffecha1']).'.txt';
          file_put_contents(APPPATH.'media/polizas/'.$_GET['poliza_nombre'], $response['data']);
          $this->addPoliza($response['data'], $response['folio']); //se registra la poliza en la BD
        }
      }

    }elseif ($this->input->get('ftipo') == '2')  //*******Polizas Egreso
    {
      if($this->input->get('ftipo3') == 'el')  //Egreso de limon
      {
        $response = $this->polizaEgresoLimon();
        //actualizamos el estado de los abonos de las boletas
        if (isset($_POST['poliza']{0}))
        {
          $idsa = array();
          foreach ($response['abonos'] as $key => $value)
            $idsa[] = $value->id_pago;
          if(count($idsa) > 0)
          {
            // $this->db->where_in('id_pago', $idsa);
            // $this->db->update('bascula_pagos', array('poliza_egreso' => 't'));

            $_GET['poliza_nombre'] = 'polizaegreso '.String::fechaATexto($_GET['ffecha1']).'.txt';
            file_put_contents(APPPATH.'media/polizas/'.$_GET['poliza_nombre'], $response['data']);
            $this->addPoliza($response['data']); //se registra la poliza en la BD
          }
        }
      }elseif($this->input->get('ftipo3') == 'ec') //Egreso de cheque
      {
        $response = $this->polizaEgreso();
        //actualizamos el estado de los abonos de las compras
        if (isset($_POST['poliza']{0}))
        {
          $idsa = array();
          foreach ($response['abonos'] as $key => $value)
            $idsa[] = $value->id_abono;
          if(count($idsa) > 0)
          {
            // $this->db->where_in('id_abono', $idsa);
            // $this->db->update('compras_abonos', array('poliza_egreso' => 't'));

            $_GET['poliza_nombre'] = 'polizaegreso '.String::fechaATexto($_GET['ffecha1']).'.txt';
            file_put_contents(APPPATH.'media/polizas/'.$_GET['poliza_nombre'], $response['data']);
            $this->addPoliza($response['data']); //se registra la poliza en la BD
          }
        }
      }else //egreso de gasto
      {
        $response = $this->polizaEgreso('otros');
        //actualizamos el estado de los abonos de las facturas
        if (isset($_POST['poliza']{0}))
        {
          $idsa = array();
          // foreach ($response['abonos'] as $key => $value)
          //   $idsa[] = $value->id_abono;
          // if(count($idsa) > 0)
          // {
            // $this->db->where_in('id_abono', $idsa);
            // $this->db->update('compras_abonos', array('poliza_egreso' => 't'));

            $_GET['poliza_nombre'] = 'polizaegreso '.String::fechaATexto($_GET['ffecha1']).'.txt';
            file_put_contents(APPPATH.'media/polizas/'.$_GET['poliza_nombre'], $response['data']);
            $this->addPoliza($response['data'], $response['folio']); //se registra la poliza en la BD
          // }
        }
      }

    }

    return $response;
  }

  public function addPoliza($txtpoliza, $folio=null){
    $folio = $folio!=null? $folio: $this->input->get('ffolio');
    if($this->input->get('ftipo') == '3') //diarios
      $tipo2 = $this->input->get('ftipo2');
    elseif($this->input->get('ftipo') == '2') //egresos
      $tipo2 = $this->input->get('ftipo3');
    else
      $tipo2 = 'i';
    $data = array(
      'tipo'     => $this->input->get('ftipo'),
      'tipo2'    => $tipo2,
      'folio'    => $folio,
      'concepto' => $this->input->get('fconcepto'),
      'poliza'   => $txtpoliza,
      );
    $this->db->insert('polizas', $data);
  }


}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */