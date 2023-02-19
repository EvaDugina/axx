function enableOps(on)
{
  var childNodes = document.getElementById("mutable").getElementsByTagName('*');
  for (var node of childNodes) {
	if (!node.classList.contains('always-disabled'))
      node.disabled = !on;
  }	
  
  var roothint = document.getElementById("hint");
  roothint.style.display = (on) ?'none' :'inline-block';
}

function updateOps()
{
  var res = false;
  var Nodes = document.getElementsByClassName("enabler");
  for (var node of Nodes) {
	res = res || node.checked;
  }	
  enableOps(res);
}