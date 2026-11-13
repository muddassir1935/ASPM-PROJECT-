import os
import zipfile

def package_working_software(output_zip="[GroupCode]_WorkingSoftware.zip"):
    exclude_dirs = {'.git', 'scratch', '__pycache__', '.pytest_cache'}
    exclude_exts = {'.sqlite', '.sqlite-journal'}

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
    package_working_software("AIR_WorkingSoftware.zip")
    package_working_software("[GroupCode]_WorkingSoftware.zip")
    package_working_software("CampusConnect_WorkingSoftware.zip")
