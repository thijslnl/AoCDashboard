<?php
error_reporting(E_ALL);
$url = "https://adventofcode.com/2020/leaderboard/private/view/189709.json";
#$url = "http://localhost/189709.json";
$output = 'score.json';
$time_file = 'time.txt';
$fp = fopen($time_file, "r");
$checked = fread($fp,filesize($time_file));
fclose($fp);
if($checked <  time()-900) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: session=[session_id]"));
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $result = curl_exec($ch);
	$result = json_decode($result, true);
    curl_close($ch);
	$days = array();
	$members = array();
	$output_data = array('labels' => array());
	$n = count($result['members']);
	foreach ($result['members'] as $user => $values) {
		foreach ($values['completion_day_level'] as $day => $stars) {
			if(!array_key_exists($day, $days)) {
				$days[$day] = array();
			}
			foreach ($stars as $star => $achieved) {
				if(!array_key_exists($star, $days[$day])) {
					$days[$day][$star] = array();
				}
				$days[$day][$star][$user] = $achieved['get_star_ts'];
				if(!array_key_exists($user, $members)) {
					$members[$user] = array('label' => $values['name']);
				}
			}
		}
	}
	
	#var_dump($members);
	ksort($days);
	
	foreach ($days as $day => $stars) {
		foreach ($stars as $star => $member) {
			asort($days[$day][$star]);
		}
		ksort($days[$day]);
	}
	
	$member_score = array();
	$members_scores = $members;
	$members_total_scores = $members;
	$members_ranks = $members;
	
	foreach ($days as $day => $stars) {
		foreach ($stars as $star => $achieved) {
			$i = 0;
			foreach ($achieved as $member_id => $timing) {
				#print($day . " " . $star . " " . $member_id);
				if(!array_key_exists($member_id, $member_score)){
					$member_score[$member_id] = 0;
				}
				if(!in_array($day . '_' . $star, $output_data['labels'])) {
					$output_data['labels'][] = $day . '_' . $star;
				}
				if ($day > 1){
					$member_score[$member_id] += ($n - $i);
				}
				$members_scores[$member_id]['data'][] = ($n - $i);
				$members_ranks[$member_id]['data'][] = $i;
				$members_total_scores[$member_id]['data'][] = $member_score[$member_id];
				$i++;
			}
		}
	}
	
	function rank_int_desc($a, $b) {
		if (max($a['data'])==max($b['data'])) return 0;
		return (max($a['data'])<max($b['data']))?1:-1;
	}
	
	function rank_int_asc($a, $b) {
		if (max($a['data'])==max($b['data'])) return 0;
		return (max($a['data'])<max($b['data']))?-1:1;
	}
	uasort($members_scores, "rank_int_desc");
	uasort($members_total_scores, "rank_int_desc");
	uasort($members_ranks, "rank_int_asc");
	array_splice($members_scores, 0, 0);
	array_splice($members_total_scores, 0, 0);
	array_splice($members_ranks, 0, 0);
	$output_data['total_scores'] = $members_total_scores;
	$output_data['scores'] = $members_scores;
	$output_data['ranks'] = $members_ranks;
	$fp = fopen($output, 'w');
	fwrite($fp, json_encode($output_data));
	fclose($fp);
    $fp = fopen($time_file, 'w');
    fwrite($fp, time());
    fclose($fp);
}
?>
<html>
    <head>
        <title>GoT AoC 2020 Rankings</title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js" integrity="sha512-d9xgZrVZpmmQlfonhQUvTR7lMPtO7NkZMkA0ABN3PHCbKA5nqylQ/yWlFAyY6hYgdF1Qh6nYiuADWwKB4C2WSw==" crossorigin="anonymous"></script>
    </head>
    <body>
		<div style="height: 20px; width: 90%; margin: auto;">
			<button onclick="location.href='aoc.php'">Total Scores</button><button onclick="location.href='aoc.php?chart=scores'">Star Scores</button><button onclick="location.href='aoc.php?chart=ranks'">Rank</button>
		</div>
        <div style="height: 90%; width: 90%; margin: auto;">
			<canvas id="chart" ></canvas>
		</div>
        <script>
			function getQueryVariable(variable)
			{
			   var query = window.location.search.substring(1);
			   var vars = query.split("&");
			   for (var i=0;i<vars.length;i++) {
				   var pair = vars[i].split("=");
				   if(pair[0] == variable){return pair[1];}
			   }
			   return(false);
			}
			
			var randomColorGenerator = function () { 
				return '#' + (Math.random().toString(16) + '0000000').slice(2, 8); 
			}
			
			function loadJSON(callback) {   
				var xobj = new XMLHttpRequest();
				xobj.overrideMimeType("application/json");
				xobj.open('GET', './score.json', true);
				xobj.onreadystatechange = function () {
					if (xobj.readyState == 4 && xobj.status == "200") {
						callback(xobj.responseText);
					}
				};
				xobj.send(null);  
			}
			
			loadJSON(function(response) {
				var actual_data = JSON.parse(response);
				var type = "total_scores";
				if (getQueryVariable('chart') == "scores" || getQueryVariable('chart') == "ranks") {
					type = getQueryVariable('chart');
				} 
				var datasets = Object.values(actual_data[type]);
				for (var u in datasets){
					if(datasets[u]['label'] == null) {
						datasets.splice(u,1);
					}
				}
				for (var u in datasets) {
					datasets[u]['fill'] = false;
					datasets[u]['borderColor'] = randomColorGenerator;
					if (u > 5){
						datasets[u]['hidden'] = true;
					}
				}
				var ctx = document.getElementById('chart').getContext('2d');
				var myChart = new Chart(ctx, {
					type: 'line',
					data: {
						labels: actual_data['labels'],
						datasets: datasets,
					},
					options: {
						responsive: true,
						scales: {
							yAxes: [{
								ticks: {
									beginAtZero:true
								}
							}]
						}
					}
				});
			});
            
        </script>
    </body>
</html>