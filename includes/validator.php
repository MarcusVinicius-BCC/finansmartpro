<?php
/**
 * Validador Centralizado - FinanSmart Pro
 */

class Validator {
    
    private $errors = [];
    private $data = [];
    
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    /**
     * Validar campo obrigatório
     */
    public function required($field, $message = null) {
        if (empty($this->data[$field])) {
            $this->errors[$field] = $message ?? "O campo {$field} é obrigatório.";
        }
        return $this;
    }
    
    /**
     * Validar email
     */
    public function email($field, $message = null) {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "Email inválido.";
        }
        return $this;
    }
    
    /**
     * Validar tamanho mínimo
     */
    public function min($field, $min, $message = null) {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = $message ?? "Mínimo de {$min} caracteres.";
        }
        return $this;
    }
    
    /**
     * Validar tamanho máximo
     */
    public function max($field, $max, $message = null) {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = $message ?? "Máximo de {$max} caracteres.";
        }
        return $this;
    }
    
    /**
     * Validar se campo corresponde a outro
     */
    public function match($field, $matchField, $message = null) {
        if (!empty($this->data[$field]) && $this->data[$field] !== $this->data[$matchField]) {
            $this->errors[$field] = $message ?? "Os campos não correspondem.";
        }
        return $this;
    }
    
    /**
     * Validar número
     */
    public function numeric($field, $message = null) {
        if (!empty($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "Deve ser um número.";
        }
        return $this;
    }
    
    /**
     * Validar data
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        if (!empty($this->data[$field])) {
            $d = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "Data inválida.";
            }
        }
        return $this;
    }
    
    /**
     * Validar valor monetário
     */
    public function money($field, $message = null) {
        if (!empty($this->data[$field])) {
            $clean = str_replace(['R$', '.', ' '], '', $this->data[$field]);
            $clean = str_replace(',', '.', $clean);
            
            if (!is_numeric($clean)) {
                $this->errors[$field] = $message ?? "Valor monetário inválido.";
            }
        }
        return $this;
    }
    
    /**
     * Validar se valor está em lista
     */
    public function in($field, $values, $message = null) {
        if (!empty($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field] = $message ?? "Valor inválido.";
        }
        return $this;
    }
    
    /**
     * Validação customizada
     */
    public function custom($field, $callback, $message = null) {
        if (!empty($this->data[$field])) {
            if (!$callback($this->data[$field])) {
                $this->errors[$field] = $message ?? "Validação falhou.";
            }
        }
        return $this;
    }
    
    /**
     * Verificar se validação passou
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Verificar se validação falhou
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Obter erros
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Obter primeiro erro
     */
    public function firstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    /**
     * Obter dados validados
     */
    public function validated() {
        $validated = [];
        foreach ($this->data as $key => $value) {
            if (!isset($this->errors[$key])) {
                $validated[$key] = $value;
            }
        }
        return $validated;
    }
}
