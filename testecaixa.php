<?php
require_once 'funcoes.php';
atualizarCaixa('abrir', 100);
echo "Caixa aberto manualmente.<br>";

$dados = buscarStatusCaixa();
echo "<pre>";
print_r($dados);
echo "</pre>";