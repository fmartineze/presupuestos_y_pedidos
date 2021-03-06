<?php

/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2015-2017    Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2017         Itaca Software Libre contacta@itacaswl.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('almacen.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('forma_pago.php');
require_model('pedido_cliente.php');
require_model('pedido_proveedor.php');
require_model('proveedor.php');
require_model('serie.php');

class informe_pedidos extends fs_controller
{
   public $agente;
   public $almacen;
   public $codagente;
   public $codalmacen;
   public $coddivisa;
   public $codpago;
   public $codserie;
   public $desde;
   public $divisa;
   public $estado;
	public $forma_pago;
   public $hasta;
   public $multi_almacen;
   public $pedidos_cli;
   public $pedidos_pro;
   public $serie;
   
	private $where_compras;
   private $where_ventas;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDOS), 'informes', FALSE, TRUE);
   }
   
   protected function private_core()
   {
      /// declaramos los objetos sólo para asegurarnos de que existen las tablas
      $this->pedido_cli = new pedido_cliente();
      $this->pedido_pro = new pedido_proveedor();
      
      $this->agente = new agente();
      $this->almacen = new almacen();
		$this->divisa = new divisa();
		$this->forma_pago = new forma_pago();
      $this->serie = new serie();
      
      $fsvar = new fs_var();
      $this->multi_almacen = $fsvar->simple_get('multi_almacen');
      
      $this->desde = Date('01-m-Y', strtotime('-14 months'));
      if( isset($_REQUEST['desde']) )
      {
         $this->desde = $_REQUEST['desde'];
      }
      
      $this->hasta = Date('t-m-Y');
      if( isset($_REQUEST['hasta']) )
      {
         $this->hasta = $_REQUEST['hasta'];
      }  
      
      $this->codserie = FALSE;
      if( isset($_REQUEST['codserie']) )
      {
         $this->codserie = $_REQUEST['codserie'];
      }
      
      $this->codpago = FALSE;
      if( isset($_REQUEST['codpago']) )
      {
         $this->codpago = $_REQUEST['codpago'];
      }
      
      $this->codagente = FALSE;
      if( isset($_REQUEST['codagente']) )
      {
         $this->codagente = $_REQUEST['codagente'];
      }
      
      $this->codalmacen = FALSE; 
      if( isset($_REQUEST['codalmacen']) )
      {
         $this->codalmacen = $_REQUEST['codalmacen'];
      }

      $this->coddivisa = $this->empresa->coddivisa;
      if( isset($_REQUEST['coddivisa']) )
      {
         $this->coddivisa = $_REQUEST['coddivisa'];
      }
      
      $this->estado = '';
      if( isset($_REQUEST['estado']) )
      {
         $this->estado = $_REQUEST['estado'];
      }
      
      $this->set_where();
   }
   
   private function set_where()
   {
      $this->where_compras = " WHERE fecha >= ".$this->empresa->var2str($this->desde)
              ." AND fecha <= ".$this->empresa->var2str($this->hasta);
      
		if($this->codserie)
      {
			$this->where_compras .= " AND codserie = ".$this->empresa->var2str($this->codserie);
      }

		if($this->codagente)
      {
			$this->where_compras .= " AND codagente = ".$this->empresa->var2str($this->codagente);
      }

		if($this->codalmacen)
      {
			$this->where_compras .= " AND codalmacen = ".$this->empresa->var2str($this->codalmacen);
      }
      
		if($this->coddivisa)
      {
         $this->where_compras .= " AND coddivisa = ".$this->empresa->var2str($this->coddivisa);
		}
      
      if($this->codpago)
      {
			$this->where_compras .= " AND codpago = ".$this->empresa->var2str($this->codpago);
      }
      
      $this->where_ventas = $this->where_compras;
      if($this->estado != '')
      {
         switch($this->estado)
         {
            case '0':
               $this->where_compras .= " AND idalbaran IS NULL";
               $this->where_ventas .= " AND idalbaran IS NULL AND status = '0'";
               break;
            
            case '1':
               $this->where_compras .= " AND idalbaran IS NOT NULL";
               $this->where_ventas .= " AND status = '1'";
               break;
            
            case '2':
               $this->where_compras .= " AND 1 = 2";
               $this->where_ventas .= " AND status = '2'";
               break;
         }
      }
   }

   public function stats_months()
   {
      $stats = array();
      $stats_cli = $this->stats_months_aux('pedidoscli');
      $stats_pro = $this->stats_months_aux('pedidosprov');
      $meses = array(
          1 => 'ene',
          2 => 'feb',
          3 => 'mar',
          4 => 'abr',
          5 => 'may',
          6 => 'jun',
          7 => 'jul',
          8 => 'ago',
          9 => 'sep',
          10 => 'oct',
          11 => 'nov',
          12 => 'dic'
      );
      
      foreach($stats_cli as $i => $value)
      {
      	$mesletra = "";
      	$ano = "";
      	
      	if( !empty($value['month']) )
      	{
	      	$mesletra = $meses[intval(substr((string)$value['month'], 0, strlen((string)$value['month'])-2))];
	      	$ano = substr((string)$value['month'], -2);
      	}
	
         $stats[$i] = array(
             'month' => $mesletra.$ano , 
             'total_cli' => round($value['total'], FS_NF0),
             'total_pro' => 0
         );
      }
      
      foreach($stats_pro as $i => $value)
      {
         $stats[$i]['total_pro'] = round($value['total'], FS_NF0);
      }
      
      return $stats;
   }
   
   private function stats_months_aux($table_name = 'pedidoscli')
   {
      $stats = array();
      
      /// inicializamos los resultados
      foreach($this->date_range($this->desde, $this->hasta, '+1 month', 'my') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('month' => $i, 'total' => 0);
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
      {
         $sql_aux = "to_char(fecha,'FMMMYY')";

      }
      else
      {
         $sql_aux = "DATE_FORMAT(fecha, '%m%y')";
      }
      
      $sql = "SELECT ".$sql_aux." as mes, SUM(neto) as total FROM ".$table_name;
      if($table_name == 'pedidoscli')
      {
         $sql .= $this->where_ventas;
      }
      else
      {
         $sql .= $this->where_compras;
      }
      $sql .= " GROUP BY ".$sql_aux." ORDER BY mes ASC;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['mes']);
            $stats[$i]['total'] = floatval($d['total']);
         }
      }
      
      return $stats;
   }
   
   public function stats_years()
   {
      $stats = array();
      $stats_cli = $this->stats_years_aux('pedidoscli');
      $stats_pro = $this->stats_years_aux('pedidosprov');
      
      foreach($stats_cli as $i => $value)
      {
         $stats[$i] = array(
             'year' => $value['year'],
             'total_cli' => round($value['total'], FS_NF0),
             'total_pro' => 0
         );
      }
      
      foreach($stats_pro as $i => $value)
      {
         $stats[$i]['total_pro'] = round($value['total'], FS_NF0);
      }
      
      return $stats;
   }
   
   private function stats_years_aux($table_name = 'pedidoscli', $num = 4)
   {
      $stats = array();
      
      /// inicializamos los resultados
      foreach($this->date_range($this->desde, $this->hasta, '+1 year', 'Y') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('year' => $i, 'total' => 0);
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
      {
         $sql_aux = "to_char(fecha,'FMYYYY')";
      }
      else
         $sql_aux = "DATE_FORMAT(fecha, '%Y')";
      
      $sql = "SELECT ".$sql_aux." as ano, sum(neto) as total FROM ".$table_name;
      if($table_name == 'pedidoscli')
      {
         $sql .= $this->where_ventas;
      }
      else
      {
         $sql .= $this->where_compras;
      }
      $sql .= " GROUP BY ".$sql_aux." ORDER BY ano ASC;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['ano']);
            $stats[$i]['total'] = floatval($d['total']);
         }
      }
      
      return $stats;
   }
   
   private function date_range($first, $last, $step = '+1 day', $format = 'd-m-Y' )
   {
      $dates = array();
      $current = strtotime($first);
      $last = strtotime($last);
      
      while( $current <= $last )
      {
         $dates[] = date($format, $current);
         $current = strtotime($step, $current);
      }
      
      return $dates;
   } 

   public function stats_series($tabla = 'pedidosprov')
   {
      $stats = array();
      
      $sql  = "select codserie,sum(neto) as total from ".$tabla;
		if($tabla == 'pedidoscli')
      {
         $sql .= $this->where_ventas;
      }
      else
      {
         $sql .= $this->where_compras;
      }
      $sql .= " group by codserie order by total desc;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $serie = $this->serie->get($d['codserie']);
            if($serie)
            {
               $stats[] = array(
                   'txt' => $serie->descripcion,
                   'total' => round( floatval($d['total']), FS_NF0)
               );
            }
            else
            {
               $stats[] = array(
                   'txt' => $d['codserie'],
                   'total' => round( floatval($d['total']), FS_NF0)
               );
            }
         }
      }
      
      return $stats;
   }

   public function stats_agentes($tabla = 'pedidosprov')
   {
      $stats = array();
      
      $sql  = "select codagente,sum(neto) as total from ".$tabla;
		if($tabla == 'pedidoscli')
      {
         $sql .= $this->where_ventas;
      }
      else
      {
         $sql .= $this->where_compras;
      }
      $sql .= " group by codagente order by total desc;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            if( is_null($d['codagente']) )
            {
               $stats[] = array(
                   'txt' => 'Ninguno',
                   'total' => round( floatval($d['total']), FS_NF0)
               );
            }
            else
            {
               $agente = $this->agente->get($d['codagente']);
               if($agente)
               {
                  $stats[] = array(
                      'txt' => $agente->get_fullname(),
                      'total' => round( floatval($d['total']), FS_NF0)
                  );
               }
               else
               {
                  $stats[] = array(
                      'txt' => $d['codagente'],
                      'total' => round( floatval($d['total']), FS_NF0)
                  );
               }
            }
         }
      }
      
      return $stats;
   }
   
   public function stats_almacenes($tabla = 'pedidosprov')
   {
      $stats = array();
      
      $sql  = "select codalmacen,sum(neto) as total from ".$tabla;
		if($tabla == 'pedidoscli')
      {
         $sql .= $this->where_ventas;
      }
      else
      {
         $sql .= $this->where_compras;
      }
		$sql .= " group by codalmacen order by total desc;"; 
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $alma = $this->almacen->get($d['codalmacen']);
            if($alma)
            {
               $stats[] = array(
                   'txt' => $alma->nombre,
                   'total' => round( floatval($d['total']), FS_NF0)
               );
            }
            else
            {
               $stats[] = array(
                   'txt' => $d['codalmacen'],
                   'total' => round( floatval($d['total']), FS_NF0)
               );
            }
         }
      }
      
      return $stats;
   }

   public function stats_formas_pago($tabla = 'pedidosprov')
   {
      $stats = array();
      
      $sql  = "select codpago,sum(neto) as total from ".$tabla;
		if($tabla == 'pedidoscli')
      {
         $sql .= $this->where_ventas;
      }
      else
      {
         $sql .= $this->where_compras;
      }
      $sql .=" group by codpago order by total desc;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $formap = $this->forma_pago->get($d['codpago']);
            if($formap)
            {
               $stats[] = array(
                   'txt' => $formap->descripcion,
                   'total' => round( floatval($d['total']), FS_NF0)
               );
            }
            else
            {
               $stats[] = array(
                   'txt' => $d['codpago'],
                   'total' => round( floatval($d['total']), FS_NF0)
               );
            }
         }
      }
      
      return $stats;
   }
   
   public function stats_estados($tabla = 'pedidosprov')
   {
      $stats = array();
      
		if($tabla == 'pedidoscli')
		{
	      $stats = $this->stats_estados_pedidoscli();
      }
      else
      {
         /// aprobados
	      $sql  = "select sum(neto) as total from ".$tabla;
			$sql .= $this->where_compras;
      	$sql .=" and idalbaran is not null order by total desc;";
         
         $data = $this->db->select($sql);
         if($data)
         {
            if( floatval($data[0]['total']) )
            {
               $stats[] = array(
                   'txt' => 'aprobado',
                   'total' => round( floatval($data[0]['total']), FS_NF0)
               );
            }
         }
         
         /// pendientes
	      $sql  = "select sum(neto) as total from ".$tabla;
			$sql .= $this->where_compras;
      	$sql .=" and idalbaran is null order by total desc;";
         
         $data = $this->db->select($sql);
         if($data)
         {
            if( floatval($data[0]['total']) )
            {
               $stats[] = array(
                   'txt' => 'pendiente',
                   'total' => round( floatval($data[0]['total']), FS_NF0)
               );
            }
         }
      }
      
      return $stats;
   }
   
   private function stats_estados_pedidoscli()
   {
      $stats = array();
      $tabla = 'pedidoscli';
      
		$sql  = "select status,sum(neto) as total from ".$tabla;
		$sql .= $this->where_ventas;
      $sql .=" group by status order by total desc;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         $estados = array(
             0 => 'pendiente',
             1 => 'aprobado',
             2 => 'rechazado',
             3 => 'validado parcialmente'
         );
         
         foreach($data as $d)
         {
            $stats[] = array(
                'txt' => $estados[$d['status']],
		          'total' => round( floatval($d['total']), FS_NF0)
            );
         }
      }
      
      return $stats;
   }
   
   /**
    * Esta función sirve para generar el javascript necesario para que la vista genere
    * las gráficas, ahorrando mucho código.
    * @param type $data
    * @param type $chart_id
    * @return string
    */
   public function generar_chart_pie_js(&$data, $chart_id)
   {
      $js_txt = '';
      
      if($data)
      {
         echo "var ".$chart_id."_labels = [];\n";
         echo "var ".$chart_id."_data = [];\n";
         
         foreach($data as $d)
         {
            echo $chart_id.'_labels.push("'.$d['txt'].'");'."\n";
            echo $chart_id.'_data.push("'.$d['total'].'");'."\n";
         }
         
         /// hacemos el apaño para evitar el problema de charts.js con tabs en boostrap
         echo "var ".$chart_id."_ctx = document.getElementById('".$chart_id."').getContext('2d');\n";
         echo $chart_id."_ctx.canvas.height = 100;\n";
         
         echo "var ".$chart_id."_chart = new Chart(".$chart_id."_ctx, {
            type: 'pie',
            data: {
               labels: ".$chart_id."_labels,
               datasets: [
                  {
                     backgroundColor: default_colors,
                     data: ".$chart_id."_data
                  }
               ]
            }
         });";
      }
      
      return $js_txt;
   }
}
