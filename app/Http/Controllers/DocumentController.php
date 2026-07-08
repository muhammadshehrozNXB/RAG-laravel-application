<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use FPDF;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documentService)
    {
    }

    public function index()
    {
        $documents = Document::latest()->get();
        return view('documents.index', compact('documents'));
    }

    public function create()
    {
        return view('documents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'source'      => 'required|in:file,text,database',
            'file'        => 'required_if:source,file|nullable|file|mimes:txt,md,pdf|max:10240',
            'title'       => 'required_if:source,text|nullable|string|max:255',
            'content'     => 'required_if:source,text|nullable|string|min:10',
            'db_title'    => 'required_if:source,database|nullable|string|max:255',
            'db_query'    => 'required_if:source,database|nullable|string',
            'db_host'     => 'nullable|string|max:255',
            'db_port'     => 'nullable|integer',
            'db_database' => 'nullable|string|max:255',
            'db_username' => 'nullable|string|max:255',
            'db_password' => 'nullable|string|max:255',
        ]);

        try {
            if ($request->source === 'file') {
                $document = $this->documentService->ingestFile($request->file('file'));
            } elseif ($request->source === 'text') {
                $document = $this->documentService->ingestText(
                    $request->input('title'),
                    $request->input('content')
                );
            } else {
                $document = $this->documentService->ingestDatabaseQuery(
                    $request->input('db_title'),
                    $request->input('db_query'),
                    $this->resolveDbConnection($request)
                );
            }

            return redirect()->route('documents.index')
                ->with('success', "Document \"{$document->title}\" ingested with {$document->chunk_count} chunks.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ingestion failed: ' . $e->getMessage()]);
        }
    }

    public function previewDbQuery(Request $request)
    {
        $request->validate([
            'query'       => 'required|string',
            'db_host'     => 'nullable|string',
            'db_port'     => 'nullable|integer',
            'db_database' => 'nullable|string',
            'db_username' => 'nullable|string',
            'db_password' => 'nullable|string',
        ]);

        try {
            $result = $this->documentService->runDbQuery(
                $request->input('query'),
                $this->resolveDbConnection($request),
                20
            );
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    private function resolveDbConnection(Request $request): array
    {
        $useApp = !$request->filled('db_host') && !$request->filled('db_database');

        return [
            'host'     => $useApp ? config('database.connections.mysql.host')     : $request->input('db_host'),
            'port'     => $useApp ? config('database.connections.mysql.port')     : ($request->input('db_port') ?? 3306),
            'database' => $useApp ? config('database.connections.mysql.database') : $request->input('db_database'),
            'username' => $useApp ? config('database.connections.mysql.username') : $request->input('db_username'),
            'password' => $useApp ? config('database.connections.mysql.password') : $request->input('db_password', ''),
        ];
    }

    public function show(Document $document)
    {
        $chunks = $document->chunks;
        return view('documents.show', compact('document', 'chunks'));
    }

    public function destroy(Document $document)
    {
        $title = $document->title;
        $this->documentService->deleteDocument($document);
        return redirect()->route('documents.index')
            ->with('success', "Document \"{$title}\" deleted.");
    }

    public function downloadSample()
    {
        $pdf = new FPDF();
        $pdf->SetMargins(20, 20, 20);
        $pdf->AddPage();

        // Title
        $pdf->SetFont('Helvetica', 'B', 20);
        $pdf->SetTextColor(30, 64, 175);
        $pdf->Cell(0, 12, 'Artificial Intelligence: A Brief Overview', 0, 1, 'C');
        $pdf->Ln(4);

        // Divider
        $pdf->SetDrawColor(30, 64, 175);
        $pdf->SetLineWidth(0.8);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(6);

        $sections = [
            'Introduction' => 'Artificial Intelligence (AI) refers to the simulation of human intelligence processes by machines, especially computer systems. These processes include learning, reasoning, and self-correction. AI has become one of the most transformative technologies of the 21st century, reshaping industries from healthcare to finance, transportation to education.',

            'Machine Learning' => 'Machine Learning (ML) is a subset of AI that enables systems to learn and improve from experience without being explicitly programmed. It focuses on developing computer programs that can access data and use it to learn for themselves. Common ML techniques include supervised learning, unsupervised learning, and reinforcement learning. Algorithms such as decision trees, neural networks, and support vector machines form the backbone of modern ML systems.',

            'Natural Language Processing' => 'Natural Language Processing (NLP) is a branch of AI that deals with the interaction between computers and humans through natural language. The ultimate objective of NLP is to read, decipher, understand, and make sense of human language in a valuable way. NLP tasks include text classification, sentiment analysis, machine translation, named entity recognition, and question answering. Large Language Models (LLMs) such as GPT and Claude have revolutionized the field.',

            'Retrieval-Augmented Generation' => 'Retrieval-Augmented Generation (RAG) is an AI framework that combines a retrieval system with a generative model. Instead of relying solely on a model\'s parametric knowledge, RAG fetches relevant documents from an external knowledge base and uses them as context when generating a response. This approach improves factual accuracy, allows the model to reference up-to-date information, and makes the system more transparent and verifiable.',

            'Applications of AI' => 'AI is applied across a wide range of domains. In healthcare, AI assists with medical imaging analysis, drug discovery, and personalized treatment plans. In finance, it powers fraud detection, algorithmic trading, and credit scoring. In transportation, autonomous vehicles and route optimization leverage AI. In education, adaptive learning platforms tailor content to individual students. Customer service chatbots, content recommendation engines, and cybersecurity threat detection are also powered by AI.',

            'Ethical Considerations' => 'As AI becomes more powerful and pervasive, ethical considerations grow in importance. Key concerns include algorithmic bias, where models reflect and amplify existing social inequalities. Privacy is another concern, as AI systems often require vast amounts of personal data. Transparency and explainability are critical so that decisions made by AI can be understood and audited. Ensuring AI is developed and deployed responsibly is a shared responsibility of researchers, companies, and policymakers.',

            'Future Outlook' => 'The future of AI holds enormous promise. Continued advances in compute power, data availability, and algorithmic research are expected to produce AI systems that are more capable, efficient, and aligned with human values. Research into areas such as multi-modal AI, continual learning, and AI safety will define the next decade. Organizations that invest in understanding and applying AI responsibly will be well positioned to benefit from these developments.',
        ];

        foreach ($sections as $heading => $body) {
            // Section heading
            $pdf->SetFont('Helvetica', 'B', 13);
            $pdf->SetTextColor(30, 64, 175);
            $pdf->Cell(0, 9, $heading, 0, 1);

            // Body text
            $pdf->SetFont('Helvetica', '', 11);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->MultiCell(0, 6, $body);
            $pdf->Ln(4);
        }

        // Footer note
        $pdf->SetFont('Helvetica', 'I', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 6, 'Sample document for RAG Laravel demo. Upload this PDF to test the ingestion pipeline.', 0, 1, 'C');

        return response($pdf->Output('S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="sample-ai-overview.pdf"',
        ]);
    }
}
