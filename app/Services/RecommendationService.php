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
     * Tự động lấy tham số Top-N và Ngưỡng Cosine từ CSDL, hỗ trợ gợi ý dự phòng theo Ngành.
     */
    public function getRecommendationsForStudent(string $mssv, ?int $topN = null, ?float $minThreshold = null): array
    {
        // 0. Lấy cấu hình Top-N và Ngưỡng Cosine động từ CSDL
        $config = DB::table('CauHinhGoiY')->orderBy('ma_cau_hinh', 'desc')->first();
        $topN = $topN ?? ($config->top_n ?? 10);
        $minThreshold = $minThreshold ?? ($config->nguong_cosine ?? 0.02);

        // 1. Lấy môn học đang học của sinh viên
        $student = SinhVien::with(['hocPhans' => function ($query) {
            $query->where('trang_thai', 'DangHoc');
        }])->find($mssv);

        if (!$student) return [];

        $maHocPhans = $student->hocPhans->pluck('ma_hoc_phan')->toArray();

        // 2. Cold-start: Lấy danh sách học phần theo Ngành nếu sinh viên chưa đăng ký học phần
        if (empty($maHocPhans)) {
            $maHocPhans = DB::table('ChuongTrinhDaoTao')
                ->where('ma_nganh', $student->ma_nganh)
                ->pluck('ma_hoc_phan')
                ->toArray();
        }

        // 3. Lấy nội dung đề cương môn học
        $syllabusTexts = DeCuongHocPhan::whereIn('ma_hoc_phan', $maHocPhans)
            ->pluck('noi_dung_de_cuong')
            ->implode(' ');

        // 4. Lấy lịch sử tìm kiếm gần đây
        $searchQueries = DB::table('LichSuTimKiem')
            ->where('mssv', $mssv)
            ->pluck('tu_khoa')
            ->implode(' ');

        // Tạo văn bản đại diện hồ sơ sinh viên
        $studentProfileText = trim($syllabusTexts . ' ' . $searchQueries);
        if (empty($studentProfileText)) {
            $nganh = DB::table('NganhHoc')->where('ma_nganh', $student->ma_nganh)->first();
            $studentProfileText = $nganh ? $nganh->ten_nganh : $student->ho_ten;
        }

        // 5. Lấy kho tài liệu thư viện
        $documents = TaiLieu::all();
        if ($documents->isEmpty()) return [];

        // 6. Tách từ & dựng Tập văn bản (Corpus)
        $corpus = [];
        $docIds = [];

        // Hồ sơ sinh viên ở vị trí 'PROFILE'
        $corpus['PROFILE'] = $this->tokenizeText($studentProfileText);

        foreach ($documents as $doc) {
            $combinedText = $doc->nhan_de . ' ' . $doc->thuat_ngu_chu_de . ' ' . $doc->mo_ta;
            $corpus[$doc->ma_tai_lieu] = $this->tokenizeText($combinedText);
            $docIds[] = $doc->ma_tai_lieu;
        }

        // 7. Tính trọng số TF-IDF
        $tfidfMatrix = $this->calculateTFIDF($corpus);

        // 8. Tính Cosine Similarity
        $studentVector = $tfidfMatrix['PROFILE'];
        $scores = [];

        foreach ($docIds as $docId) {
            $docVector = $tfidfMatrix[$docId];
            $similarity = $this->cosineSimilarity($studentVector, $docVector);

            if ($similarity >= $minThreshold) {
                $scores[$docId] = round($similarity, 4);
            }
        }

        // 9. Sắp xếp giảm dần và lấy Top N
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