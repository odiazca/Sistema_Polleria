<?php /** Pestañas del CRM. Variable: $tab */
$items = [['crm', 'panel', 'crm/index', 'bi-speedometer2', 'Panel CRM'], ['crm', 'clientes', 'crm/clientes', 'bi-people-fill', 'Clientes 360'], ['campanas', 'campanas', 'campanas/index', 'bi-megaphone-fill', 'Campañas y fidelidad']];
?>
<div class="crm-nav">
  <?php foreach ($items as [$mod, $k, $rt, $ic, $lb]): if (!Auth::can($mod)) continue; ?>
    <a href="<?= e(url($rt)) ?>" class="<?= ($tab ?? '') === $k ? 'on' : '' ?>"><i class="bi <?= $ic ?>"></i><?= e($lb) ?></a>
  <?php endforeach; ?>
</div>
