from paver.easy import *

PKG_NAME = 'DocTest'

@task
def build_docs(options):
    
    # Detect phpDocumentor.
    try:
        sh('which phpdoc')
    except BuildFailure:
        msg = 'You must install phpDocumentor to build documentation.'
        raise BuildFailure(msg)

    # Detect rst2phpdoc.
    try:
        sh('which rst2phpdoc.py')
    except BuildFailure:
        msg = 'You must install rst2phpdoc to build documentation.'
        raise BuildFailure(msg)
        
    html_docs_dir = path('docs/html')
    docs_rst_dir = path('docs')
    docs_build_dir = path('build/docs')
    tutorials_dir = docs_build_dir / 'tutorials'

    # Remove old docs.
    html_docs_dir.rmtree()
    
    # Remove old doc build dir.
    docs_build_dir.rmtree()
    
    # Create tutorials directory.
    tutorials_dir.makedirs()
    
    for doc_file in docs_rst_dir.walkfiles('*.rst'):
        output_file = tutorials_dir / PKG_NAME / (doc_file.namebase + '.pkg')
        
        if doc_file.name == 'main.rst':
            output_file = output_file.parent / (PKG_NAME + '.pkg')
            
        # Ensure output dir exists.
        output_file.parent.makedirs()
            
        sh('rst2phpdoc.py %s %s' % (doc_file, output_file))
        
    
    for doc_dir in docs_rst_dir.walkdirs():
        if doc_dir == 'html':
            continue
            
        for doc in doc_dir.walkfiles('*.rst'):
            print 'Parsing: %s' % doc

    
    # Run phpDocumentor on src directory.
    try:
        sh('phpdoc -ti "DocTest Documentation" -dn DocTest -o HTML:frames:l0l33t -t docs/html -d src,build/docs -ed examples -ric README.rst')
    except BuildFailure:
        msg = 'There was an error building the documentation.'
        raise BuildFailure(msg)

    sh('open docs/html/index.html')

@task
def release(options):
    pass
#     from shutil import ignore_patterns
#     from zipfile import ZipFile
#     
#     build_dir = path('build')
#     src_root = path('src')
#     build_dir.rmtree()
#     build_dir.mkdir()
#     package_dir = build_dir / 'presseo'
#     src_root.copytree(package_dir, 
#                       ignore=ignore_patterns('.gitignore', '*.less', 'vendor'))
#     vendor_root = package_dir / 'vendor'
#     vendor_root.mkdir()
#     phorms_root = path('vendor/phorms')
#     phorms_root.copytree(vendor_root / 'phorms',
#                          ignore=ignore_patterns('.git', 'build_doc.sh', 
#                                                 'changelog', 'doc', 'examples'))
#     archive_file = path('PresSEO-%s.zip' % version)
#     archive = ZipFile(archive_file, 'w')
#     for filename in package_dir.walk():
#         if not path(filename).isdir():
#             archive.write(filename, filename.replace('build/', ''))
#     archive.close()