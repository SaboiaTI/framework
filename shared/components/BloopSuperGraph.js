/* ------------------------------------------------------------------------------------------------------
 * BloopSuperGraph.js
 * 
 * classe do component 'BloopSuperGraph'
 * comportamento de interface para gráficos construídos a partir de tabelas HTML simples
 * @author			Saboia Tecnologia da Informação <relacionamento@saboia.com.br>
 * @link			http://www.saboia.com.br
 * @version 		1.0
 * @dependencies	jQuery, basicLib
 * ------------------------------------------------------------------------------------------------------
*/

var BloopSuperGraph = function(htmlElement, grColors) {

	var that = this;
	var graphColors;
	
	if (!grColors) {
		graphColors = new Array("#CCFFFF",
								"#FFCC00",
								"#CCCCB8",
								"#FF3300",
								"#00CC33",
								"#E1FAFA",
								"#D9D9AD",
								"#E5E5B8",
								"#E6E6CF",
								"#F2F2DF",
								"#66FF99",
								"#F4F4F4");
	} else {
		graphColors = grColors;
	}
	
	
	
	this.createSuperGraph = function() {
		
		if (htmlElement) {

			htmlElement.css({"display":"none"});

			var data 		= new Array();
			var values 		= new Array();
			var maxValue 	= 0;
			var label;
			var value;
			var strGraph;

			$(htmlElement).find("tbody").find("tr").each(function(index){

				label = $(this).find("td").first().text();
				value = $(this).find("td").last().text();
				data.push({"label":label,"value":value});
				values.push(value);
			});

			for (i=0; i<data.length; i++) {
				maxValue = Math.max(maxValue, values[i]);
			}

			if (htmlElement.attr("data-x-type") && htmlElement.attr("data-x-type") == "bar") {

				strGraph  = '';
				strGraph += '<svg width="100%" height="'+ (Math.max(((data.length * 10) + 40),150)) +'">';
				strGraph += 	'<defs>';

				strGraph += 		'<filter id="dropshadow" x="-10%" y="-10%" width="200%" height="200%">';
				strGraph += 			'<feOffset result="offOut" in="SourceAlpha" dx="0" dy="1" />';
				strGraph += 			'<feGaussianBlur result="blurOut" in="offOut" stdDeviation="2" />';
				
				strGraph += 			'<feComponentTransfer xmlns="http://www.w3.org/2000/svg">';
				strGraph += 				'<feFuncA type="linear" slope="0.5" />';
				strGraph += 			'</feComponentTransfer>';
				
				strGraph += 			'<feMerge xmlns="http://www.w3.org/2000/svg">';
				strGraph += 				'<feMergeNode />';
				strGraph += 				'<feMergeNode in="SourceGraphic" />';
				strGraph += 			'</feMerge>';
				strGraph += 		'</filter>';

				strGraph += 	'</defs>';

				for (i=0; i<data.length; i++) {

					if (data[i]["label"].toUpperCase() == "TOTAL") { continue; }

					strGraph += 	'<g class="bar" transform="translate(0,'+10*i+')" style="cursor:auto;" data-y="'+(10*i)+'" data-label="'+data[i]["label"]+'" data-value="'+data[i]["value"]+' registro(s)" title="'+data[i]["value"]+' registro(s)">';
					strGraph += 		'<text x="100" y="8" text-anchor="end" style="font-size:10px;fill:#999;">'+data[i]["label"]+'</text>';
					strGraph += 		'<rect x="105" y="0" height="9" width="195" style="fill:#F4F4F4;"></rect>';
					strGraph += 		'<rect x="105" y="0" height="9" width="'+ (data[i]["value"] * 100 / maxValue) * (195 / 100) +'" style="fill:'+graphColors[i]+';"></rect>';
					strGraph += 		'<rect x="'+ ((data[i]["value"] * 100 / maxValue) * (195 / 100) + 105) +'" y="0" height="9" width="1" style="fill:#DDD;"></rect>';
					strGraph += 		'<rect x="'+ ((data[i]["value"] * 100 / maxValue) * (195 / 100) + 106) +'" y="0" height="9" width="1" style="fill:#FFF;"></rect>';
					strGraph += 	'</g>';
				}

				strGraph += 	'<g class="tip" transform="translate(110,'+10*i+')" data-y="'+(10*i)+'" style="display:none;">';
				strGraph += 		'<g filter="url(#dropshadow)">';
				strGraph += 			'<rect x="0" y="0" height="40" width="140" rx="3" ry="3" style="fill:#FFF;fill-opacity:0.9;"></rect>';
				strGraph += 			'<path d="M7 0 L10 -5 L13 0 Z" style="fill:#FFF;fill-opacity:0.9;"></path>';
				strGraph += 		'</g>';

				strGraph += 		'<text id="line1" x="5" y="14" style="kerning:-0.5;font-size:11px;fill:#999;"></text>';
				strGraph += 		'<line x1="5" y1="19" x2="130" y2="19" style="stroke:#09F;stroke-width:0.2"/>';
				strGraph += 		'<text id="line2" x="5" y="32" style="kerning:-0.5;font-size:12px;fill:#333;"></text>';
				
				strGraph += 	'</g>';
				strGraph += '</svg>';
				
				htmlElement.after(strGraph);
				
				htmlElement.siblings('svg').find('g.bar').bind('mouseenter', function(event){
					
					$(this).closest('svg').find('g.tip').find('text#line1').text($(this).attr('data-label'));
					$(this).closest('svg').find('g.tip').find('text#line2').text($(this).attr('data-value'));
					$(this).closest('svg').find('g.tip')[0].setAttribute("transform", "translate(110," + (parseInt($(this).attr('data-y')) + 10) + ")");
					$(this).closest('svg').find('g.tip').show();
					
				});
				
				htmlElement.siblings('svg').find('g.bar').bind('mouseleave', function(event){
					$(this).closest('svg').find('g.tip')[0].setAttribute("transform", "translate(110," + (parseInt($(this).closest('svg').find('g.tip').attr('data-y')) + 10) + ")");
					$(this).closest('svg').find('g.tip').hide();
				});
				
				
			}
			
		}
		
	};

	this.showTip = function() {
		
		var strTip  = '<g class="tip"><text style="font-size:10px;fill:#999;">a</text></g>';
		
		alert(strTip);
		
	}
}
