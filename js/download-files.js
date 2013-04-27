function downloadFile(link) {
	$('#loader').remove();
	var iframe = document.createElement("iframe");
	iframe.id = 'loader';
	iframe.style.display = "none";
	iframe.src = link;
	document.getElementsByTagName("body")[0].appendChild(iframe);
}