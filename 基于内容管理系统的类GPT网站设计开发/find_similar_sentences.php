<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Input handling
    $text = $_POST['text'] ?? '';
    $session_id = $_POST['session_id'] ?? null;
    $initial_similarity_threshold = 0.5;
    $min_similarity_threshold = 0.1;
    $current_threshold = $initial_similarity_threshold;

    $similar_sentences = [];  // Store all similar sentences

    // Get sentence embedding from FastAPI
    $url_get_embedding = 'http://127.0.0.1:8001/get-sentence-embedding/';
    $data_get_embedding = json_encode(['text' => $text]);
    $options_get_embedding = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => $data_get_embedding,
        ],
    ];
    $context_get_embedding = stream_context_create($options_get_embedding);
    $result_get_embedding = file_get_contents($url_get_embedding, false, $context_get_embedding);

    if ($result_get_embedding === false) {
        echo json_encode(['error' => 'Failed to get sentence embedding from FastAPI.']);
        exit();
    }

    $response_get_embedding = json_decode($result_get_embedding, true);
    $input_embedding = $response_get_embedding['embedding'] ?? null;

    if (!$input_embedding) {
        echo json_encode(['error' => 'Failed to decode sentence embedding from FastAPI response.']);
        exit();
    }

    // Connect to MySQL database
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "transapi";

    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            throw new Exception('Failed to connect to MySQL database.');
        }

        // Query both tmx_data and txt_data tables, compute cosine similarity
        $sql = "
            SELECT id, source_text, target_text, source_vector, target_vector 
            FROM tmx_data
            UNION ALL
            SELECT id, text AS source_text, '' AS target_text, vector AS source_vector, NULL AS target_vector 
            FROM txt_data
        ";
        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception('Failed to query MySQL database.');
        }

        $found_similar_sentence = false;  // Flag to track if similar sentence is found

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $source_vector = json_decode($row['source_vector']);
                if ($source_vector === null) {
                    continue;  // Skip rows with invalid vector data
                }

                $dot_product = 0;
                $input_norm = 0;
                $source_norm = 0;
                for ($i = 0; $i < count($input_embedding); $i++) {
                    $dot_product += $input_embedding[$i] * $source_vector[$i];
                    $input_norm += $input_embedding[$i] * $input_embedding[$i];
                    $source_norm += $source_vector[$i] * $source_vector[$i];
                }
                $input_norm = sqrt($input_norm);
                $source_norm = sqrt($source_norm);
                $similarity = $dot_product / ($input_norm * $source_norm);

                // Check if similarity meets current threshold
                if ($similarity > $current_threshold) {
                    $similar_sentences[] = [
                        'similarity' => $similarity,
                        'source_text' => $row['source_text'],
                        'target_text' => $row['target_text']
                    ];
                    $found_similar_sentence = true;  // Set flag to true if a match is found
                }
            }

            // If no similar sentence found and current threshold is above minimum, reduce threshold
            while (!$found_similar_sentence && $current_threshold > $min_similarity_threshold) {
                $current_threshold -= 0.1;  // Adjust threshold downwards
                $result->data_seek(0);  // Reset result pointer to start of result set

                // Clear similar_sentences array for new threshold check
                $similar_sentences = [];

                while ($row = $result->fetch_assoc()) {
                    $source_vector = json_decode($row['source_vector']);
                    if ($source_vector === null) {
                        continue;  // Skip rows with invalid vector data
                    }

                    $dot_product = 0;
                    $input_norm = 0;
                    $source_norm = 0;
                    for ($i = 0; $i < count($input_embedding); $i++) {
                        $dot_product += $input_embedding[$i] * $source_vector[$i];
                        $input_norm += $input_embedding[$i] * $input_embedding[$i];
                        $source_norm += $source_vector[$i] * $source_vector[$i];
                    }
                    $input_norm = sqrt($input_norm);
                    $source_norm = sqrt($source_norm);
                    $similarity = $dot_product / ($input_norm * $source_norm);

                    // Check if similarity meets current threshold
                    if ($similarity > $current_threshold) {
                        $similar_sentences[] = [
                            'similarity' => $similarity,
                            'source_text' => $row['source_text'],
                            'target_text' => $row['target_text']
                        ];
                        $found_similar_sentence = true;  // Set flag to true if a match is found
                    }
                }
            }
        }

        $conn->close();

        // Sort sentences by descending similarity
        usort($similar_sentences, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Prepare context text for chat
        $context_text = !empty($similar_sentences) ?
            $similar_sentences[0]['source_text'] . "\n" . $similar_sentences[0]['target_text'] :
            '';

        // 构造发送给 FastAPI 的数据
        $data = json_encode([
            'text' => $text,
            'context' => $context_text,
            #'session_id' => $session_id,
            'session_id' => ""
        ]);

        // Send data to FastAPI for chat
        $url_chat = 'http://127.0.0.1:8001/chat/';
        $options_chat = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => $data,
            ],
        ];
        $context_chat = stream_context_create($options_chat);
        $result_chat = file_get_contents($url_chat, false, $context_chat);

        if ($result_chat === false) {
            echo json_encode(['error' => 'Failed to chat with the assistant via FastAPI.']);
            exit();
        }

        $response_chat = json_decode($result_chat, true);

        if (!$response_chat || !isset($response_chat['session_id'], $response_chat['assistant_message'])) {
            echo json_encode(['error' => 'Invalid response from FastAPI chat endpoint.']);
            exit();
        }

        // Output the response
        echo json_encode([
            'session_id' => $response_chat['session_id'],
            'assistant_message' => $response_chat['assistant_message'],
            'similar_sentences' => $similar_sentences
        ]);

    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}
?>
