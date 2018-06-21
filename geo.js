function zoom(){
  //var vw = document.documentElement.clientWidth/100;
  var vw = window.innerWidth/100;
  var points = document.getElementById('map').getElementsByClassName('point');
  
  for (i = 0; i < points.length; ++i) {
    points[i].style.width = (vw*6)+'px';
    points[i].style.height = (vw*6)+'px';
    points[i].style.borderRadius = (vw*6)+'px';
    points[i].style.lineHeight = (vw*6)+'px';
    points[i].style.marginLeft = -1*(vw*3)+'px';
    points[i].style.marginTop = -1*(vw*3)+'px';
    points[i].style.fontSize = (vw*5.5)+'px';
  } 
}

function setZoom(){
  zoom();
  window.setInterval(function(){zoom()},5000);
}
