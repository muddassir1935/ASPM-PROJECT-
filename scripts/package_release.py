import os
import zipfile

def package_working_software(output_zip="AIR_WorkingSoftware.zip"):
    exclude_dirs = {'.git', 'scratch', '__pycache__', '.pytest_cache'}
    # Deliverable binaries are shipped separately, not bundled inside the zip
    # (the report PDFs alone are ~29 MB); the zip holds source code only.
    exclude_exts = {'.sqlite', '.sqlite-journal', '.pdf', '.pptx'}

    with zipfile.ZipFile(output_zip, 'w', compression=zipfile.ZIP_DEFLATED) as z:
        for root, dirs, files in os.walk('.'):
            # prune excluded dirs
            dirs[:] = [d for d in dirs if d not in exclude_dirs and not d.startswith('.')]
            for f in files:
                if any(f.endswith(ext) for ext in exclude_exts):
                    continue
                if f == output_zip or f.endswith('.zip'):
                    continue
                file_path = os.path.join(root, f)
                arcname = os.path.relpath(file_path, '.')
                z.write(file_path, arcname)

    print(f"Working software zip created at: {output_zip}")

if __name__ == '__main__':
    # One set only: AIR_WorkingSoftware.zip, regenerated from merged main on
    # 15 Nov. Do NOT generate CampusConnect_* or [GroupCode]_* duplicates.
    package_working_software("AIR_WorkingSoftware.zip")
