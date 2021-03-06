<?php
/*
 * This file is part of facturacion_base
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
namespace FacturaScripts\model;

/**
 * Una cuenta bancaria de un cliente.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class cuenta_banco_cliente extends \fs_model
{

    /**
     * Clave primaria. Varchar(6).
     * @var string 
     */
    public $codcuenta;

    /**
     * Código del cliente.
     * @var string 
     */
    public $codcliente;
    public $descripcion;
    public $iban;
    public $swift;

    /**
     * ¿Es la cuenta principal del cliente?
     * @var boolean
     */
    public $principal;

    /**
     * Fecha en la que se firmó el mandato para autorizar la domiciliación de recibos.
     * @var string 
     */
    public $fmandato;

    public function __construct($data = FALSE)
    {
        parent::__construct('cuentasbcocli');
        if ($data) {
            $this->codcliente = $data['codcliente'];
            $this->codcuenta = $data['codcuenta'];
            $this->descripcion = $data['descripcion'];
            $this->iban = $data['iban'];
            $this->swift = $data['swift'];
            $this->principal = $this->str2bool($data['principal']);

            $this->fmandato = NULL;
            if ($data['fmandato']) {
                $this->fmandato = date('d-m-Y', strtotime($data['fmandato']));
            }
        } else {
            $this->codcliente = NULL;
            $this->codcuenta = NULL;
            $this->descripcion = NULL;
            $this->iban = NULL;
            $this->swift = NULL;
            $this->principal = TRUE;
            $this->fmandato = NULL;
        }
    }

    protected function install()
    {
        return '';
    }

    /**
     * Devuelve el IBAN con o sin espacios.
     * @param string $espacios
     * @return string
     */
    public function iban($espacios = FALSE)
    {
        if ($espacios === TRUE) {
            $txt = '';
            $iban = str_replace(' ', '', $this->iban);
            for ($i = 0; $i < strlen($iban); $i += 4) {
                $txt .= substr($iban, $i, 4) . ' ';
            }
            return $txt;
        }

        return str_replace(' ', '', $this->iban);
    }

    public function url()
    {
        if (is_null($this->codcliente)) {
            return '#';
        }

        return 'index.php?page=ventas_cliente&cod=' . $this->codcliente . '#cuentasb';
    }

    public function get($cod)
    {
        $data = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE codcuenta = " . $this->var2str($cod) . ";");
        if ($data) {
            return new \cuenta_banco_cliente($data[0]);
        }

        return FALSE;
    }

    private function get_new_codigo()
    {
        $sql = "SELECT MAX(" . $this->db->sql_to_int('codcuenta') . ") as cod FROM " . $this->table_name . ";";
        $data = $this->db->select($sql);
        if ($data) {
            return (string) (1 + intval($data[0]['cod']));
        }

        return '1';
    }

    public function exists()
    {
        if (is_null($this->codcuenta)) {
            return FALSE;
        }

        return $this->db->select("SELECT * FROM " . $this->table_name
                . " WHERE codcuenta = " . $this->var2str($this->codcuenta) . ";");
    }

    public function save()
    {
        $this->descripcion = $this->no_html($this->descripcion);

        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET descripcion = " . $this->var2str($this->descripcion) .
                ", codcliente = " . $this->var2str($this->codcliente) .
                ", iban = " . $this->var2str($this->iban) .
                ", swift = " . $this->var2str($this->swift) .
                ", principal = " . $this->var2str($this->principal) .
                ", fmandato = " . $this->var2str($this->fmandato) .
                "  WHERE codcuenta = " . $this->var2str($this->codcuenta) . ";";
        } else {
            $this->codcuenta = $this->get_new_codigo();
            $sql = "INSERT INTO " . $this->table_name . " (codcliente,codcuenta,descripcion,iban,swift,principal,fmandato)" .
                " VALUES (" . $this->var2str($this->codcliente) .
                "," . $this->var2str($this->codcuenta) .
                "," . $this->var2str($this->descripcion) .
                "," . $this->var2str($this->iban) .
                "," . $this->var2str($this->swift) .
                "," . $this->var2str($this->principal) .
                "," . $this->var2str($this->fmandato) . ");";
        }

        if ($this->principal) {
            /// si esta cuenta es la principal, desmarcamos las demás
            $sql .= "UPDATE " . $this->table_name . " SET principal = false" .
                " WHERE codcliente = " . $this->var2str($this->codcliente) .
                " AND codcuenta != " . $this->var2str($this->codcuenta) . ";";
        }

        return $this->db->exec($sql);
    }

    public function delete()
    {
        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE codcuenta = " . $this->var2str($this->codcuenta) . ";");
    }

    public function all_from_cliente($codcli)
    {
        $clist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codcliente = " . $this->var2str($codcli)
            . " ORDER BY codcuenta DESC;";

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $clist[] = new \cuenta_banco_cliente($d);
            }
        }

        return $clist;
    }
}
