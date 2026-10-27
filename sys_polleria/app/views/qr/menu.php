<?php /** Carta digital pública (móvil) */
$empresa = cfg('empresa_nombre', APP_NAME);
$logo = cfg('empresa_logo');
$brand = preg_match('/^#[0-9a-fA-F]{6}$/', cfg('color_principal', '#F97316')) ? cfg('color_principal', '#F97316') : '#F97316';
$money = ['symbol' => cfg('moneda_simbolo', 'S/'), 'pos' => cfg('moneda_posicion', 'antes'), 'dec' => (int)cfg('decimales', 2), 'decSep' => cfg('sep_decimal', '.'), 'thSep' => cfg('sep_miles', ',')];
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Carta · <?= e($empresa) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--brand:<?= e($brand) ?>;--bg:#FFF9F2;--ink:#1F2937;--mut:#6B7280;--line:#F1E7DA}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);padding-bottom:90px;-webkit-tap-highlight-color:transparent}
.top{background:linear-gradient(135deg,#FFEDD5,#FEF3C7);padding:20px 16px 16px;border-radius:0 0 26px 26px;box-shadow:0 10px 30px -18px rgba(234,88,12,.5)}
.top .row{display:flex;align-items:center;gap:12px}
.lg{width:48px;height:48px;border-radius:14px;background:var(--brand);color:#fff;display:grid;place-items:center;font-size:24px;overflow:hidden;flex:none}
.lg img{width:100%;height:100%;object-fit:cover}
h1{font-size:1.1rem;font-weight:800}
.mesa{display:inline-block;margin-top:2px;background:#fff;border-radius:999px;padding:3px 12px;font-size:.78rem;font-weight:700;color:var(--brand)}
.cats{display:flex;gap:8px;overflow-x:auto;padding:14px 16px 6px;position:sticky;top:0;background:var(--bg);z-index:5;scrollbar-width:none}
.cats::-webkit-scrollbar{display:none}
.chip{flex:none;border:1px solid var(--line);background:#fff;border-radius:999px;padding:8px 14px;font-weight:700;font-size:.82rem;color:var(--mut)}
.chip.on{background:var(--brand);border-color:var(--brand);color:#fff}
.list{padding:8px 16px}
.cat-t{font-weight:800;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:var(--mut);margin:16px 0 8px}
.card{display:flex;gap:12px;background:#fff;border:1px solid var(--line);border-radius:18px;padding:10px;margin-bottom:10px}
.im{width:76px;height:76px;border-radius:14px;background:#FFF1E0;color:var(--brand);display:grid;place-items:center;font-size:30px;flex:none;overflow:hidden}
.im img{width:100%;height:100%;object-fit:cover}
.inf{flex:1;min-width:0;display:flex;flex-direction:column}
.inf b{font-size:.92rem}.inf small{color:var(--mut);font-size:.76rem;line-height:1.3;margin:2px 0 auto}
.pr{display:flex;justify-content:space-between;align-items:center;margin-top:6px}
.pr span{font-weight:800;color:var(--brand)}
.add{border:0;background:var(--brand);color:#fff;border-radius:12px;width:36px;height:36px;font-size:18px}
.qty{display:flex;align-items:center;gap:8px}.qty button{border:0;background:#FFEDD5;color:var(--brand);width:30px;height:30px;border-radius:10px;font-weight:800}
.bar{position:fixed;left:12px;right:12px;bottom:12px;z-index:20}
.bar button{width:100%;border:0;background:var(--brand);color:#fff;border-radius:18px;padding:15px 18px;font-weight:800;display:flex;justify-content:space-between;box-shadow:0 14px 30px -10px rgba(234,88,12,.7);font-family:inherit;font-size:1rem}
.sheet{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:30;display:none;align-items:flex-end}
.sheet.on{display:flex}
.pan{background:#fff;width:100%;max-height:92vh;overflow:auto;border-radius:26px 26px 0 0;padding:18px 16px 24px}
.pan h2{font-size:1.1rem;font-weight:800;margin-bottom:10px;display:flex;justify-content:space-between}
.ln{display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--line)}
.ln .n{flex:1;font-weight:600;font-size:.9rem}.ln input.nt{width:100%;border:1px solid var(--line);border-radius:10px;padding:6px 8px;font-size:.78rem;margin-top:4px;font-family:inherit}
input.f,textarea.f{width:100%;border:1px solid var(--line);border-radius:12px;padding:11px 12px;font-family:inherit;font-size:.92rem;margin-top:8px}
.tt{display:flex;justify-content:space-between;font-weight:800;font-size:1.1rem;margin:14px 0}
.go{width:100%;border:0;background:var(--brand);color:#fff;border-radius:14px;padding:14px;font-weight:800;font-size:1rem;font-family:inherit}
.go:disabled{opacity:.6}
.ok{text-align:center;padding:30px 10px}.ok i{font-size:60px;color:#16A34A}
.err{max-width:420px;margin:80px auto;text-align:center;padding:20px}.err i{font-size:56px;color:var(--brand)}
</style>
</head>
<body>
<?php if (!$mesa): ?>
  <div class="err"><i class="bi bi-qr-code"></i><h1 style="margin:12px 0 6px">Código QR no disponible</h1><p style="color:var(--mut)">Este código no es válido o los pedidos por QR están desactivados. Por favor llame a su mozo.</p></div>
<?php else: ?>
<div class="top"><div class="row">
  <div class="lg"><?php if ($logo): ?><img src="<?= e(upload_url($logo)) ?>" alt=""><?php else: ?><i class="bi bi-fire"></i><?php endif; ?></div>
  <div><h1><?= e($empresa) ?></h1><span class="mesa"><i class="bi bi-grid-3x3-gap-fill"></i> <?= e($mesa['nombre']) ?> · <?= e($mesa['zona']) ?></span></div>
</div></div>
<div class="cats" id="cats"><?php foreach ($cats as $i => $c): ?><button class="chip <?= $i === 0 ? 'on' : '' ?>" data-c="<?= (int)$c['id'] ?>"><?= e($c['nombre']) ?></button><?php endforeach; ?></div>
<div class="list" id="list"></div>
<div class="bar" id="bar" style="display:none"><button id="openCart"><span><i class="bi bi-bag-heart-fill"></i> Ver mi pedido (<b id="cnt">0</b>)</span><span id="sum"></span></button></div>
<div class="sheet" id="sheet"><div class="pan" id="pan"></div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
var M=<?= json_encode($money) ?>, CATS=<?= json_encode($cats, JSON_UNESCAPED_UNICODE) ?>, PRODS=<?= json_encode(array_map(fn($p) => ['id' => (int)$p['id'], 'c' => (int)$p['categoria_id'], 'n' => $p['nombre'], 'd' => $p['descripcion'], 'p' => (float)$p['precio'], 'i' => $p['imagen'] ? upload_url($p['imagen']) : ''], $prods), JSON_UNESCAPED_UNICODE) ?>;
var CSRF=<?= json_encode(csrf_token()) ?>, TOKEN=<?= json_encode($_GET['t'] ?? '') ?>, BASE=<?= json_encode(BASE_URL) ?>;
var cart={}, notes={}, cat=CATS.length?CATS[0].id:0;
function fm(v){var s=Math.abs(v).toFixed(M.dec),p=s.split('.');p[0]=p[0].replace(/\B(?=(\d{3})+(?!\d))/g,M.thSep);var r=p.join(M.dec>0?M.decSep:'');return M.pos==='antes'?M.symbol+' '+r:r+' '+M.symbol}
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
var list=document.getElementById('list');
function render(){
  var h='';CATS.forEach(function(c){ if(c.id!==cat) return;
    PRODS.filter(function(p){return p.c===c.id}).forEach(function(p){var q=cart[p.id]||0;
      h+='<div class="card"><div class="im">'+(p.i?'<img src="'+esc(p.i)+'" alt="">':'<i class="bi bi-egg-fried"></i>')+'</div><div class="inf"><b>'+esc(p.n)+'</b><small>'+esc(p.d)+'</small><div class="pr"><span>'+fm(p.p)+'</span>'+
      (q?'<div class="qty"><button data-m="'+p.id+'">−</button><b>'+q+'</b><button data-a="'+p.id+'">+</button></div>':'<button class="add" data-a="'+p.id+'"><i class="bi bi-plus-lg"></i></button>')+'</div></div></div>';});});
  list.innerHTML=h||'<p style="text-align:center;color:#6B7280;padding:40px 0">Sin productos disponibles</p>';
  var n=0,t=0;PRODS.forEach(function(p){var q=cart[p.id]||0;n+=q;t+=q*p.p});
  document.getElementById('cnt').textContent=n;document.getElementById('sum').textContent=fm(t);document.getElementById('bar').style.display=n?'block':'none';
}
document.getElementById('cats').addEventListener('click',function(e){var b=e.target.closest('.chip');if(!b)return;cat=+b.dataset.c;document.querySelectorAll('.chip').forEach(function(x){x.classList.toggle('on',x===b)});render();scrollTo(0,0)});
list.addEventListener('click',function(e){var a=e.target.closest('[data-a]'),m=e.target.closest('[data-m]');
  if(a){var i=+a.dataset.a;cart[i]=Math.min(20,(cart[i]||0)+1)}else if(m){var j=+m.dataset.m;cart[j]=(cart[j]||0)-1;if(cart[j]<=0)delete cart[j]}else return;render()});
var sheet=document.getElementById('sheet'),pan=document.getElementById('pan');
function cartView(){
  var h='<h2>Mi pedido <button onclick="document.getElementById(\'sheet\').classList.remove(\'on\')" style="border:0;background:none;font-size:22px">×</button></h2>',t=0;
  PRODS.forEach(function(p){var q=cart[p.id];if(!q)return;t+=q*p.p;
    h+='<div class="ln"><div class="n">'+esc(p.n)+'<br><small style="color:#6B7280">'+fm(p.p)+'</small><input class="nt" data-n="'+p.id+'" placeholder="Nota (ej. sin ají)" maxlength="120" value="'+esc(notes[p.id]||'')+'"></div><div class="qty"><button data-m2="'+p.id+'">−</button><b>'+q+'</b><button data-a2="'+p.id+'">+</button></div></div>';});
  h+='<input class="f" id="nom" placeholder="Su nombre (opcional)" maxlength="80"><textarea class="f" id="nts" rows="2" placeholder="Comentarios para el mozo (opcional)" maxlength="250"></textarea>'+
     '<div class="tt"><span>Total</span><span>'+fm(t)+'</span></div><button class="go" id="send">Enviar pedido al mozo</button><p style="text-align:center;color:#6B7280;font-size:.74rem;margin-top:8px">Un mozo confirmará su pedido antes de enviarlo a cocina.</p>';
  pan.innerHTML=h;
}
document.getElementById('openCart').onclick=function(){cartView();sheet.classList.add('on')};
sheet.addEventListener('click',function(e){
  if(e.target===sheet){sheet.classList.remove('on');return}
  var a=e.target.closest('[data-a2]'),m=e.target.closest('[data-m2]');
  if(a){var i=+a.dataset.a2;cart[i]=Math.min(20,cart[i]+1)}else if(m){var j=+m.dataset.m2;cart[j]--;if(cart[j]<=0)delete cart[j]}
  else if(e.target.id==='send'){send();return}else return;
  var nom=(document.getElementById('nom')||{}).value,nts=(document.getElementById('nts')||{}).value;
  if(!Object.keys(cart).length){sheet.classList.remove('on');render();return}
  cartView();document.getElementById('nom').value=nom||'';document.getElementById('nts').value=nts||'';render();
});
sheet.addEventListener('input',function(e){if(e.target.dataset.n)notes[e.target.dataset.n]=e.target.value});
function send(){
  var btn=document.getElementById('send');btn.disabled=true;btn.textContent='Enviando…';
  var items=Object.keys(cart).map(function(id){return {id:+id,cantidad:cart[id],nota:notes[id]||''}});
  fetch(BASE+'/index.php?r=qr/pedir&t='+TOKEN,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({items:items,nombre:document.getElementById('nom').value,notas:document.getElementById('nts').value})})
  .then(function(r){return r.json()}).then(function(d){
    if(!d.ok){btn.disabled=false;btn.textContent='Enviar pedido al mozo';alert(d.msg||'No se pudo enviar');return}
    cart={};notes={};render();
    pan.innerHTML='<div class="ok"><i class="bi bi-hourglass-split" id="ic"></i><h2 style="justify-content:center;margin-top:10px" id="st">¡Pedido enviado!</h2><p style="color:#6B7280" id="sd">Esperando la confirmación del mozo…</p><button class="go" style="margin-top:18px" onclick="document.getElementById(\'sheet\').classList.remove(\'on\')">Seguir viendo la carta</button></div>';
    var iv=setInterval(function(){fetch(BASE+'/index.php?r=qr/estado&t='+TOKEN+'&id='+d.id,{headers:{Accept:'application/json'}}).then(function(r){return r.json()}).then(function(s){
      if(s.estado==='aceptada'){clearInterval(iv);document.getElementById('ic').className='bi bi-check-circle-fill';document.getElementById('st').textContent='¡Pedido confirmado!';document.getElementById('sd').textContent='Su pedido ya está en cocina. ¡Buen provecho!'}
      else if(s.estado==='rechazada'){clearInterval(iv);document.getElementById('ic').className='bi bi-x-circle-fill';document.getElementById('ic').style.color='#DC2626';document.getElementById('st').textContent='No se pudo confirmar';document.getElementById('sd').textContent='Por favor consulte con su mozo.'}
    }).catch(function(){})},4000);
  }).catch(function(){btn.disabled=false;btn.textContent='Enviar pedido al mozo';alert('Error de conexión')});
}
render();
});
</script>
<?php endif; ?>
</body>
</html>
