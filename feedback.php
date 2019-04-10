<?php 
	if (!$included) {
		exit;
	}
?>
<section>
	<style>
		
		.star {
			cursor: pointer;
			font-size: 16pt;
		}
	</style>
<?php 
	if (isset($_POST['code'])) {
		$code = $_POST['code'];
	}
	else {
		$code = $_GET['code'];
	}

	$result = $db->query('SELECT id, title, speaker, link FROM presentations
		WHERE code = "' . $db->escape_string($code) . '"
		AND `datetime` > ' . (time() - ($code_validity * 3600 * 24)))
		or die('Database error 5. Please try again in a minute.');
	
	if ($result->num_rows == 0) {
		die('Dieser Code ist nicht bekannt oder abgelaufen. Hast Du Dich vertippt? <a href="./">Versuche es erneut!</a>');
	}

	$row = $result->fetch_row();
	$presentationid = $row[0];
	$title = $row[1];
	$speaker = $row[2];
	$link = $row[3];

	echo '<h1>Feedback geben zu <i>' . htmlspecialchars($title) . '</i></h1>';
	echo '<h3>Erstellt von ' . htmlspecialchars($speaker) . '</h3>';
	

	if (isset($_POST['code'])) {
		$db->query("INSERT INTO presentation_feedback (presentationid) VALUES($presentationid)")
			or die('Database error 762');

		$feedbackid = $db->insert_id;
		$i = 0;
		foreach ($_POST['q'] as $answer) {
			$response = $db->escape_string($answer);
			$db->query("INSERT INTO presentation_question_responses
				(feedbackid, response, sequenceNumber)
				VALUES($feedbackid, '$response', $i)")
				or die('Database error 1525');
			$i++;
		}
		echo "<p><h1>Vielen Dank für das Feedback!</strong></h1>";

if (!empty($link)) {
		if (substr($link, 0, 4) !== 'http') {
			$reallink = 'http://' . $link;
			$reallink = htmlspecialchars($reallink);
		}
		else {
			$reallink = htmlspecialchars($link);
		}
		echo "<a href='$reallink'>Hier findest Du weitere Informationen im Kontext der bewerteten Präsentation/ Veranstaltung</a>";
	}
	echo "<p>Hier gelangst Du zurück zur <a href=https://bittefeedback.de>Startseite von BitteFeedback.de</a></p>";

	}
	else {
		$result = $db->query('SELECT pq.sequenceNumber, pq.question, pq.type
			FROM presentations p
			INNER JOIN presentation_questions pq ON pq.presentationid = p.id
			WHERE p.code = "' . $db->escape_string($code) . '"
			ORDER BY pq.sequenceNumber')
			or die('Database error 7. Please try again in a minute.');

		$questions = [];
		while ($row = $result->fetch_row()) {
			$questions[$row[0]] = [$row[1], $row[2]];
		}

		?>
			<form method=post>
				<input type=hidden name=code value="<?php echo htmlspecialchars($_GET['code']); ?>">
				<?php 
					foreach ($questions as $n=>$question) {
						list($question, $type) = $question;
						echo '<strong>' . htmlspecialchars($question) . '</strong><br>';

						if ($type == 1) {
							// 5-star rating
							$wstar = '&#9734;'; // white star
							$bstar = '&#9733;'; // black star
							$i = 0;
							while ($i < 5) {
								echo "<span class='sq$n-$i star' onclick='star($n, $i);'>$wstar</span>";
								$i++;
							}
							echo "<input type=hidden name='q[]' id='q$n' value='-1'>";
							echo "<br><br>";
						}

						else if ($type == 2) {
							// text field
							echo "<textarea name='q[]' cols=75 rows=2></textarea><br><br>";
						}

						else {
							continue;
						}
					}
				?>
				<input type=submit value=Senden>
			</form>
			<script>
				function $(q) {
					return document.querySelector(q);
				}
				function star(question_n, star_n) {
					for (var i = 0; i <= star_n; i++) {
						$(".sq" + question_n + "-" + i).innerHTML = "&#9733;";
					}
					for (var i = star_n + 1; i < 5; i++) {
						$(".sq" + question_n + "-" + i).innerHTML = '&#9734;';
					}
					$("#q" + question_n).value = star_n + 1;
				}
			</script>
		<?php 
	}
?>
</section>
