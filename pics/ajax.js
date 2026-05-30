function loadXMLDoc(a,b,c,d){
    var x=new XMLHttpRequest();
    x.open('POST','/click',true);
    x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    x.onreadystatechange=function(){
        if(x.readyState==4&&x.status==200){
            var t=x.responseText;
            if(c!=1)return;
            var el=document.querySelector('[data-id="'+a+'-'+b+'"]');
            if(el)el.textContent=t;
        }
    };
    x.send('a='+a+'&b='+encodeURIComponent(b)+'&c='+c+'&d='+d);
}
document.addEventListener('click',function(e){
    var img=e.target.closest('.article-img');
    if(!img||!img.getAttribute('data-src'))return;
    var ov=document.createElement('div');
    ov.className='img-overlay';
    ov.innerHTML='<button class="img-overlay-close">&times;</button><img src="'+img.getAttribute('data-src')+'" class="img-overlay-img">';
    document.body.appendChild(ov);
    function rm(){ov.remove();document.removeEventListener('keydown',kb);}
    function kb(ev){if(ev.key==='Escape'||ev.key===' '){ev.preventDefault();rm();}}
    ov.addEventListener('click',function(ev){if(ev.target===ov)rm();});
    ov.querySelector('.img-overlay-close').addEventListener('click',rm);
    document.addEventListener('keydown',kb);
});

// ── Popup overlay ──
var _popupInit = false;

function _initPopup() {
    if (_popupInit) return;
    _popupInit = true;
    var closeTxt = typeof popupCloseTxt !== 'undefined' ? popupCloseTxt : '关闭';
    var ov = document.createElement('div');
    ov.id = 'opendiv';
    ov.innerHTML = '<div id="divcontent"><div id="divinto"></div></div><button class="popup-close" onclick="closediv()">&times; ' + closeTxt + '</button>';
    document.body.appendChild(ov);
}

function openshow(type) {
    _initPopup();
    var x = new XMLHttpRequest();
    x.open('GET', '/ajax?type=' + encodeURIComponent(type), true);
    x.onreadystatechange = function() {
        if (x.readyState == 4 && x.status == 200) {
            try {
                var d = JSON.parse(x.responseText);
                if (d.success) {
                    document.getElementById('divinto').innerHTML = d.html;
                    document.getElementById('opendiv').style.display = 'flex';
                }
            } catch(e) {}
        }
    };
    x.send();
}

function closediv() {
    var ov = document.getElementById('opendiv');
    if (ov) ov.style.display = 'none';
    var di = document.getElementById('divinto');
    if (di) di.innerHTML = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var ov = document.getElementById('opendiv');
        if (ov && ov.style.display === 'block') closediv();
    }
});

document.addEventListener('click', function(e) {
    if (e.target.id === 'opendiv') closediv();
});

