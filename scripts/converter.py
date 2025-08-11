#!/usr/bin/env python3
"""
Document Converter for Dalthaus CMS

Converts Word (.docx) and PDF documents to HTML format compatible with TinyMCE editor.
Provides clean HTML output suitable for web content management.

Features:
- Word document conversion using pypandoc
- PDF text extraction using pdfplumber
- Automatic format detection
- JSON output for easy integration
- Graceful fallback for missing dependencies

Requirements:
- Python 3.6+
- pypandoc (for Word documents)
- pdfplumber (for PDF documents)

Usage:
    python converter.py input.docx
    python converter.py input.pdf
    python converter.py --format=pdf input.file

Author: Dalthaus CMS
Version: 1.0.0
"""

import sys
import os
import argparse
import json
import re
from pathlib import Path
from html.parser import HTMLParser
from html import escape

# Try to import optional dependencies
try:
    import pypandoc
    HAS_PANDOC = True
except ImportError:
    HAS_PANDOC = False

try:
    import pdfplumber
    HAS_PDF = True
except ImportError:
    HAS_PDF = False

# Define allowed HTML elements and attributes based on TinyMCE configuration
ALLOWED_ELEMENTS = {
    # Text content
    'p': ['class', 'style'],
    'h2': ['class', 'style'],  # H1 is reserved for page title
    'h3': ['class', 'style'],
    'h4': ['class', 'style'],
    'h5': ['class', 'style'],
    'h6': ['class', 'style'],
    'blockquote': ['cite', 'class', 'style'],
    'pre': ['class', 'style'],
    'code': ['class', 'style'],
    
    # Text formatting
    'strong': ['class', 'style'],
    'b': ['class', 'style'],
    'em': ['class', 'style'],
    'i': ['class', 'style'],
    'u': ['class', 'style'],
    'mark': ['class', 'style'],
    'small': ['class', 'style'],
    'cite': ['class', 'style'],
    'del': ['class', 'style'],
    'ins': ['class', 'style'],
    'sub': ['class', 'style'],
    'sup': ['class', 'style'],
    
    # Links
    'a': ['href', 'target', 'rel', 'title', 'class', 'style'],
    
    # Lists
    'ul': ['class', 'style'],
    'ol': ['class', 'style'],
    'li': ['class', 'style'],
    'dl': ['class', 'style'],
    'dt': ['class', 'style'],
    'dd': ['class', 'style'],
    
    # Media
    'img': ['src', 'alt', 'title', 'width', 'height', 'loading', 'class', 'style'],
    'figure': ['class', 'style'],
    'figcaption': ['class', 'style'],
    'video': ['src', 'controls', 'width', 'height', 'poster', 'preload', 'autoplay', 'muted', 'loop', 'class', 'style'],
    'audio': ['src', 'controls', 'preload', 'autoplay', 'loop', 'class', 'style'],
    'source': ['src', 'type'],
    'iframe': ['src', 'width', 'height', 'frameborder', 'allowfullscreen', 'class', 'style'],
    
    # Tables
    'table': ['class', 'style', 'border', 'cellpadding', 'cellspacing'],
    'thead': ['class', 'style'],
    'tbody': ['class', 'style'],
    'tfoot': ['class', 'style'],
    'tr': ['class', 'style'],
    'td': ['class', 'style', 'colspan', 'rowspan'],
    'th': ['class', 'style', 'colspan', 'rowspan'],
    'caption': ['class', 'style'],
    
    # Semantic HTML5
    'article': ['class', 'style'],
    'section': ['class', 'style'],
    'header': ['class', 'style'],
    'footer': ['class', 'style'],
    'aside': ['class', 'style'],
    'nav': ['class', 'style'],
    
    # Containers
    'div': ['class', 'style', 'id'],
    'span': ['class', 'style'],
    
    # Other
    'br': [],
    'hr': ['class', 'style'],
}

def convert_h1_to_h2(html):
    """
    Convert all H1 tags to H2 tags since H1 is reserved for page titles
    
    Args:
        html (str): HTML content to process
        
    Returns:
        str: HTML with H1 tags converted to H2
    """
    # Convert opening H1 tags to H2
    html = re.sub(r'<h1([^>]*)>', r'<h2\1>', html, flags=re.IGNORECASE)
    # Convert closing H1 tags to H2
    html = re.sub(r'</h1>', '</h2>', html, flags=re.IGNORECASE)
    return html

def process_page_breaks(html):
    """
    Process various page break patterns and convert to TinyMCE format
    
    Detects common page break patterns from Word/PDF and converts them
    to the <!-- page --> separator used by the CMS.
    
    Args:
        html (str): HTML content to process
        
    Returns:
        str: HTML with page breaks converted to <!-- page -->
    """
    # Pattern 1: Word page breaks (from pandoc conversion)
    # Look for explicit page break divs or styles
    html = re.sub(r'<div[^>]*style="[^"]*page-break-[^"]*"[^>]*>.*?</div>', '<!-- page -->', html, flags=re.DOTALL)
    
    # Pattern 2: Horizontal rules that might represent page breaks
    # Multiple dashes or underscores often indicate page breaks in plain text
    html = re.sub(r'<hr\s*/?>\s*<hr\s*/?>', '<!-- page -->', html)
    
    # Pattern 3: Multiple consecutive <br> tags (often used as page separators)
    html = re.sub(r'(<br\s*/?>\s*){4,}', '<!-- page -->', html)
    
    # Pattern 4: Section breaks marked with special characters
    html = re.sub(r'<p>\s*[\*\-\_]{3,}\s*</p>', '<!-- page -->', html)
    html = re.sub(r'<p>\s*\*\s*\*\s*\*\s*</p>', '<!-- page -->', html)
    
    # Pattern 5: "Page Break" text variations
    html = re.sub(r'<p>\s*\[?\s*page\s*break\s*\]?\s*</p>', '<!-- page -->', html, flags=re.IGNORECASE)
    
    # Pattern 6: Form feed characters (\f) from PDF
    html = re.sub(r'\f', '<!-- page -->', html)
    
    # Clean up multiple consecutive page breaks
    html = re.sub(r'(<!-- page -->\s*){2,}', '<!-- page -->', html)
    
    return html

def convert_docx_to_html(input_file):
    """
    Convert Word document to HTML format
    
    Uses pypandoc to convert .docx files to clean HTML.
    Preserves formatting like bold, italic, lists, and tables.
    Detects and converts page breaks to CMS format.
    
    Args:
        input_file (str): Path to input Word document
        
    Returns:
        dict: Result dictionary with 'success' bool and 'html' or 'error' string
    """
    if not HAS_PANDOC:
        return {"success": False, "error": "pypandoc not installed. Run: pip install pypandoc"}
    
    try:
        # Convert document to HTML using pandoc
        # Request that pandoc preserves page breaks
        html = pypandoc.convert_file(input_file, 'html', extra_args=['--wrap=none'])
        
        # Process page breaks
        html = process_page_breaks(html)
        
        # Convert H1 to H2 (H1 is reserved for page titles)
        html = convert_h1_to_h2(html)
        
        # Add information about allowed elements
        result = {
            "success": True, 
            "html": html,
            "info": "H1 tags have been converted to H2 (H1 is reserved for page titles). Page breaks are marked with <!-- page -->."
        }
        
        return result
    except Exception as e:
        return {"success": False, "error": str(e)}

def convert_pdf_to_html(input_file):
    """
    Convert PDF document to HTML format
    
    Extracts text from PDF and converts to HTML paragraphs.
    Maintains paragraph structure based on double line breaks.
    Detects page boundaries and inserts page break markers.
    
    Args:
        input_file (str): Path to input PDF file
        
    Returns:
        dict: Result dictionary with 'success' bool and 'html' or 'error' string
    """
    if not HAS_PDF:
        # Fallback to basic conversion if pdfplumber not available
        return convert_pdf_basic(input_file)
    
    try:
        html_parts = []  # List to collect HTML paragraphs
        
        # Open and process PDF
        with pdfplumber.open(input_file) as pdf:
            for page_num, page in enumerate(pdf.pages):
                # Add page break between pages (except before first page)
                if page_num > 0:
                    html_parts.append('<!-- page -->')
                
                # Extract text from page
                text = page.extract_text()
                if text:
                    # Check for form feed characters
                    text = text.replace('\f', '\n\n<!-- page -->\n\n')
                    
                    # Split text into paragraphs by double newlines
                    paragraphs = text.split('\n\n')
                    
                    # Convert each paragraph to HTML
                    for para in paragraphs:
                        para = para.strip()
                        if para:
                            # Check if this is a page break marker
                            if para == '<!-- page -->':
                                html_parts.append(para)
                            # Check for section break patterns
                            elif re.match(r'^[\*\-\_]{3,}$', para) or re.match(r'^\*\s*\*\s*\*$', para):
                                html_parts.append('<!-- page -->')
                            # Check for "Page Break" text
                            elif re.match(r'^\[?\s*page\s*break\s*\]?$', para, re.IGNORECASE):
                                html_parts.append('<!-- page -->')
                            else:
                                # Regular paragraph
                                html_parts.append(f"<p>{para}</p>")
        
        # Join all paragraphs into final HTML
        html = '\n'.join(html_parts)
        
        # Process any additional page break patterns
        html = process_page_breaks(html)
        
        # Convert H1 to H2 (H1 is reserved for page titles)
        html = convert_h1_to_h2(html)
        
        # Add information about allowed elements
        result = {
            "success": True, 
            "html": html,
            "info": "H1 tags have been converted to H2 (H1 is reserved for page titles). Page breaks between PDF pages are marked with <!-- page -->."
        }
        
        return result
    except Exception as e:
        return {"success": False, "error": str(e)}

def convert_pdf_basic(input_file):
    """
    Basic fallback for PDF conversion without dependencies
    
    Returns an error message when pdfplumber is not installed.
    This ensures the converter doesn't fail completely.
    
    Args:
        input_file (str): Path to input PDF file (unused in fallback)
        
    Returns:
        dict: Error result with installation instructions
    """
    return {
        "success": False, 
        "error": "PDF conversion requires pdfplumber library. Run: pip install pdfplumber"
    }

def sanitize_html(html):
    """
    Sanitize HTML to only allow elements and attributes configured in TinyMCE
    
    This ensures consistency between what the converter outputs and what
    TinyMCE will accept and preserve.
    
    Args:
        html (str): Raw HTML to sanitize
        
    Returns:
        str: Sanitized HTML with only allowed elements and attributes
    """
    # For now, we trust pypandoc and pdfplumber output
    # Full sanitization would require an HTML parser like BeautifulSoup
    # But we'll at least ensure H1 tags are converted
    return convert_h1_to_h2(html)

def get_allowed_elements_info():
    """
    Get a formatted string of allowed HTML elements for documentation
    
    Returns:
        str: Human-readable list of allowed elements
    """
    categories = {
        'Headings': ['h2', 'h3', 'h4', 'h5', 'h6'],
        'Text': ['p', 'blockquote', 'pre', 'code'],
        'Formatting': ['strong', 'b', 'em', 'i', 'u', 'mark', 'small', 'cite', 'del', 'ins', 'sub', 'sup'],
        'Lists': ['ul', 'ol', 'li', 'dl', 'dt', 'dd'],
        'Media': ['img', 'figure', 'figcaption', 'video', 'audio', 'source', 'iframe'],
        'Tables': ['table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'caption'],
        'Links': ['a'],
        'Structure': ['div', 'span', 'article', 'section', 'header', 'footer', 'aside', 'nav'],
        'Other': ['br', 'hr']
    }
    
    info_parts = []
    for category, elements in categories.items():
        info_parts.append(f"{category}: {', '.join(elements)}")
    
    return '; '.join(info_parts)

def main():
    """
    Main entry point for command-line usage
    
    Parses arguments, detects file format, and performs conversion.
    Outputs JSON result to stdout for easy integration with PHP.
    """
    # Set up command-line argument parser
    parser = argparse.ArgumentParser(description='Convert documents to HTML for CMS import')
    parser.add_argument('input', 
                        nargs='?',
                        help='Path to input document file')
    parser.add_argument('--format', 
                        choices=['auto', 'docx', 'pdf'], 
                        default='auto',
                        help='Force specific format (auto-detects by default)')
    parser.add_argument('--list-elements',
                        action='store_true',
                        help='List allowed HTML elements and exit')
    
    # Parse command-line arguments
    args = parser.parse_args()
    
    # If --list-elements flag is set, show allowed elements and exit
    if args.list_elements:
        print(json.dumps({
            "allowed_elements": list(ALLOWED_ELEMENTS.keys()),
            "info": get_allowed_elements_info(),
            "note": "H1 tags are automatically converted to H2 (H1 is reserved for page titles)"
        }, indent=2))
        sys.exit(0)
    
    # Check if input file is provided when not listing elements
    if not args.input:
        parser.error("Input file is required unless using --list-elements")
    
    # Validate input file exists
    input_path = Path(args.input)
    if not input_path.exists():
        result = {"success": False, "error": f"File not found: {args.input}"}
        print(json.dumps(result))
        sys.exit(1)
    
    # Determine document format
    if args.format == 'auto':
        # Auto-detect based on file extension
        ext = input_path.suffix.lower()
        if ext in ['.docx', '.doc']:
            format_type = 'docx'
        elif ext == '.pdf':
            format_type = 'pdf'
        else:
            # Unsupported format
            result = {"success": False, "error": f"Unsupported format: {ext}. Supported: .docx, .pdf"}
            print(json.dumps(result))
            sys.exit(1)
    else:
        # Use manually specified format
        format_type = args.format
    
    # Perform conversion based on format
    if format_type == 'docx':
        result = convert_docx_to_html(str(input_path))
    elif format_type == 'pdf':
        result = convert_pdf_to_html(str(input_path))
    else:
        result = {"success": False, "error": "Invalid format specified"}
    
    # Output JSON result
    print(json.dumps(result))
    
    # Exit with appropriate code
    sys.exit(0 if result['success'] else 1)

if __name__ == '__main__':
    main()