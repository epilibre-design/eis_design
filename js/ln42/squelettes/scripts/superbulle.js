/*----------------------------------------------------------------------------\
|                     Une super info bulle                       |
||-----------------------------------------------------------------------------|
|                   http://www.espacejavascript.com                    |
Utilisation entre les balises <BODY> et </BODY> : 
<a href="page.html" onmouseover="montre()" onmouseout="cache()">Texte du lien</a>

<DIV ID="calque1" STYLE="visibility:hidden"><font face="verdana" size="1" color="#004080">Texte qui apparaîtra</font></div>

|                    
\----------------------------------------------------------------------------*/


function montre()
{
	if(document.getElementById)
	{
		document.getElementById("calque1").style.visibility = 'visible';
	}
	else if(document.all)
	{
		document.all["calque1"].style.visibility = 'visible';
	}
}
function cache()
{
	if(document.getElementById)
	{
		document.getElementById("calque1").style.visibility = 'hidden';
	}
	else if(document.all)
	{
		document.all["calque1"].style.visibility = 'hidden';
	}
}
</script>