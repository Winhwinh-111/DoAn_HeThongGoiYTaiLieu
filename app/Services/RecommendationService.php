<?php

namespace App\Services;

use App\Models\SinhVien;
use App\Models\TaiLieu;
use App\Models\DeCuongHocPhan;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Thuật toán gợi ý tài liệu dựa trên Content-Based Filtering (TF-IDF + Cosine Similarity)
     */
    public function getRecommendationsForStudent(string $mssv, int $topN = 10, float $minThreshold = 0.02): array
    {
        // 1. Lấy môn học đang học của sinh viên
        $student = SinhVien::with(['hocPhans' => function ($query) {
            $query->where('trang_thai', 'DangHoc');
        }])->find($mssv);

        if (!$student) return [];

        $maHocPhans = $student->hocPhans->pluck('ma_hoc_phan')->toArray();

        // 2. Lấy nội dung đề cương môn học
        $syllabusTexts = DeCuongHocPhan::whereIn('ma_hoc_phan', $maHocPhans)
            ->pluck('noi_dung_de_cuong')
            ->implode(' ');

        // 3. Lấy lịch sử tìm kiếm gần đây
        $searchQueries = DB::table('LichSuTimKiem')
            ->where('mssv', $mssv)
            ->pluck('tu_khoa')
            ->implode(' ');

        // Tạo văn bản đại diện hồ sơ sinh viên
        $studentProfileText = trim($syllabusTexts . ' ' . $searchQueries);
        if (empty($studentProfileText)) {
            $studentProfileText = $student->ho_ten;
        }

        // 4. Lấy kho tài liệu thư viện
        $documents = TaiLieu::all();
        if ($documents->isEmpty()) return [];

        // 5. Tách từ & dựng Tập văn bản (Corpus)
        $corpus = [];
        $docIds = [];

        // Hồ sơ sinh viên ở vị trí 'PROFILE'
        $corpus['PROFILE'] = $this->tokenizeText($studentProfileText);

        foreach ($documents as $doc) {
            $combinedText = $doc->nhan_de . ' ' . $doc->thuat_ngu_chu_de . ' ' . $doc->mo_ta;
            $corpus[$doc->ma_tai_lieu] = $this->tokenizeText($combinedText);
            $docIds[] = $doc->ma_tai_lieu;
        }

        // 6. Tính trọng số TF-IDF
        $tfidfMatrix = $this->calculateTFIDF($corpus);

        // 7. Tính Cosine Similarity
        $studentVector = $tfidfMatrix['PROFILE'];
        $scores = [];

        foreach ($docIds as $docId) {
            $docVector = $tfidfMatrix[$docId];
            $similarity = $this->cosineSimilarity($studentVector, $docVector);

            if ($similarity >= $minThreshold) {
                $scores[$docId] = round($similarity, 4);
            }
        }

        // 8. Sắp xếp giảm dần và lấy Top N
        arsort($scores);
        $topDocIds = array_slice(array_keys($scores), 0, $topN);

        $recommendedBooks = TaiLieu::whereIn('ma_tai_lieu', $topDocIds)->get()->keyBy('ma_tai_lieu');

        $result = [];
        foreach ($topDocIds as $id) {
            if (isset($recommendedBooks[$id])) {
                $book = $recommendedBooks[$id];
                $book->score = $scores[$id];
                $result[] = $book;
            }
        }

        return $result;
    }

    /**
     * Tách từ & loại bỏ Stopwords
     */
    private function tokenizeText(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $stopWords = ['và', 'của', 'là', 'trong', 'các', 'những', 'cho', 'về', 'với', 'khi', 'được', 'theo', 'như', 'tại'];
        return array_values(array_diff($words, $stopWords));
    }

    /**
     * Tính ma trận TF-IDF
     */
    private function calculateTFIDF(array $corpus): array
    {
        $numDocs = count($corpus);
        $docFreq = [];

        foreach ($corpus as $words) {
            $uniqueWords = array_unique($words);
            foreach ($uniqueWords as $word) {
                $docFreq[$word] = ($docFreq[$word] ?? 0) + 1;
            }
        }

        $tfidfMatrix = [];

        foreach ($corpus as $docId => $words) {
            $wordCount = count($words);
            $tf = array_count_values($words);
            $vector = [];

            foreach ($tf as $word => $count) {
                $tfVal = $count / max(1, $wordCount);
                $idfVal = log(($numDocs + 1) / (($docFreq[$word] ?? 0) + 1)) + 1;
                $vector[$word] = $tfVal * $idfVal;
            }
            $tfidfMatrix[$docId] = $vector;
        }

        return $tfidfMatrix;
    }

    /**
     * Tính độ tương đồng Cosine
     */
    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($vecA as $word => $weightA) {
            $normA += $weightA * $weightA;
            if (isset($vecB[$word])) {
                $dotProduct += $weightA * $vecB[$word];
            }
        }

        foreach ($vecB as $weightB) {
            $normB += $weightB * $weightB;
        }

        if ($normA == 0 || $normB == 0) return 0.0;

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}