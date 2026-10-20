<?php

namespace App\Service;



use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final class FileUploaderService
{
    public function __construct(
        private readonly string $uploadsDirectory,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slug($originalName)->toString();
        $fileName = $safeName.'-'.bin2hex(random_bytes(8)).'.'.$file->guessExtension();

        $file->move($this->uploadsDirectory, $fileName);

        return $fileName;
    }
}
