// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

// theme: light / dark / auto (1 tiny file = 1 hit, cached 1 year)
(function(){
  var k='neko-theme', b=document.getElementById('themeBtn'), h=document.documentElement;
  function get(){ try{return localStorage.getItem(k)||'auto'}catch(e){return 'auto'} }
  function apply(v){
    if(v==='auto'){ h.dataset.theme = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
    else h.dataset.theme=v;
  }
  var cur=get(); apply(cur);
  if(b) b.onclick=function(){
    cur = h.dataset.theme==='dark' ? 'light' : 'dark';
    try{localStorage.setItem(k,cur)}catch(e){}
    apply(cur);
  };
  try{ matchMedia('(prefers-color-scheme: dark)').addEventListener('change',function(){ if(get()==='auto') apply('auto'); }); }catch(e){}
})();
